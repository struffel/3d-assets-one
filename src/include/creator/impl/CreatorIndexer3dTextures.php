<?php

namespace creator\impl;

use asset\Asset;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Fetch;
use indexing\CreatorIndexer;
use misc\Image;
use misc\Log;
use Throwable;

class CreatorIndexer3dTextures extends CreatorIndexer
{

	private static string $apiUrl = "https://3dtextures.me/wp-json/wp/v2/";
	private static int $maxAssets = 500;

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();

		$page = 1;
		$wpOutput = [];

		$processedAssets = 0;

		$continue = true;
		do {
			$wpLink = self::$apiUrl . "posts?_embed&per_page=100&page=$page&orderby=date";
			$wpOutput = Fetch::fetchRemoteJson($wpLink);

			if ($wpOutput) {

				foreach ($wpOutput as $wpPost) {

					if (!in_array($wpPost['link'], $existingUrls)) {

						// Tags
						$tmpTags = [];
						foreach ($wpPost['_embedded']['wp:term'] as $embeddedCategory) {
							foreach ($embeddedCategory as $embeddedObject) {
								$tmpTags[] = $embeddedObject['name'];
							}
						}

						// Thumbnail

						$oldErrorReportingLevel = error_reporting();
						error_reporting(E_ERROR | E_PARSE);

						// 1st attempt
						try {
							$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['square']['source_url'];
						} catch (Throwable $e) {
							Log::write($e->getMessage() . " / 1st attempt failed... / " . $wpPost['link'], "IMG-ERROR");
						}

						// 2nd attempt
						if (!isset($tmpThumbnail)) {
							try {
								$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
							} catch (Throwable $e) {
								Log::write($e->getMessage() . " / 2nd attempt failed... / " . $wpPost['link'], "IMG-ERROR");
							}
						}

						// 3rd attempt
						if (!isset($tmpThumbnail)) {
							try {
								$tmpThumbnail = $wpPost['jetpack_featured_media_url'];
							} catch (Throwable $e) {
								Log::write($e->getMessage() . " / 3rd attempt failed... / " . $wpPost['link'], "IMG-ERROR");
							}
						}

						error_reporting($oldErrorReportingLevel);

						// Test if any attempt worked
						if (!isset($tmpThumbnail)) {
							Log::write("All attempts failed. Thumbnail could not be resolved. Skipping... / " . $wpPost['link'], "IMG-ERROR");
							continue;
						}

						// Assemble asset
						$tmpAsset = new Asset(
							id: NULL,
							name: $wpPost['title']['rendered'],
							url: $wpPost['link'],
							date: $wpPost['date'],
							tags: $tmpTags,
							thumbnailUrl: $tmpThumbnail,
							type: Type::PBR_MATERIAL,
							license: License::CC0,
							creator: Creator::THREE_D_TEXTURES,
							quirks: [Quirk::ADS]
						);

						$tmpCollection->assets[] = $tmpAsset;

						$processedAssets++;
					}
					if ($processedAssets >= self::$maxAssets) {
						$continue = false;
						break;
					}
				}
				$page++;
			} else {
				$continue = false;
			}
		} while ($continue);

		return $tmpCollection;
	}
	public static function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(Fetch::fetchRemoteData($url), 3, 3, 0.015);
	}
}
