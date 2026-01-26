<?php

namespace creator\logic;

use asset\Asset;
use asset\StoredAssetCollection;
use asset\CommonLicense;
use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use creator\Creator;

use DateTime;
use fetch\WebItemReference;
use creator\CreatorLogic;
use log\LogLevel;
use thumbnail\Thumbnail;
use log\Log;
use Throwable;

class CreatorLogic3dTextures extends CreatorLogic
{

	protected Creator $creator = Creator::THREE_D_TEXTURES;
	protected int $maxAssetsPerRun = 10;

	private string $apiUrl = "https://3dtextures.me/wp-json/wp/v2/";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();

		$page = 1;
		$wpOutput = [];

		$processedAssets = 0;

		$continue = true;
		do {
			$wpLink = $this->apiUrl . "posts?_embed&per_page=100&page=$page&orderby=date";
			$wpOutput = new WebItemReference($wpLink)->fetch()->parseAsJson();

			if ($wpOutput) {

				foreach ($wpOutput as $wpPost) {

					if (!$existingAssets->containsUrl(strtolower($wpPost['link']))) {

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
							Log::write("1st attempt failed", [$wpPost['link'], $e], LogLevel::WARNING);
						}

						// 2nd attempt
						if (!isset($tmpThumbnail)) {
							try {
								$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
							} catch (Throwable $e) {
								Log::write("2nd attempt failed", [$wpPost['link'], $e], LogLevel::WARNING);
							}
						}

						// 3rd attempt
						if (!isset($tmpThumbnail)) {
							try {
								$tmpThumbnail = $wpPost['jetpack_featured_media_url'];
							} catch (Throwable $e) {
								Log::write("3rd attempt failed", [$wpPost['link'], $e], LogLevel::WARNING);
							}
						}

						error_reporting($oldErrorReportingLevel);

						// Test if any attempt worked
						if (!isset($tmpThumbnail)) {
							Log::write("All attempts failed. Thumbnail could not be resolved. Skipping... ", $wpPost['link'], LogLevel::ERROR);
							continue;
						}

						// Assemble asset
						$tmpAsset = new ScrapedAsset(
							id: NULL,
							creatorGivenId: null,
							title: $wpPost['title']['rendered'],
							url: $wpPost['link'],
							date: new DateTime($wpPost['date']),
							tags: $tmpTags,
							type: AssetType::PBR_MATERIAL,
							creator: Creator::THREE_D_TEXTURES,
							rawThumbnailData: new WebItemReference(url: $tmpThumbnail)->fetch()->content,
							status: ScrapedAssetStatus::NEWLY_FOUND,
						);

						$tmpCollection[] = $tmpAsset;

						$processedAssets++;
					}
					if ($processedAssets >= $this->maxAssetsPerRun) {
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
}
