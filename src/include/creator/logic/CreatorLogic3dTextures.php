<?php

namespace creator\logic;

use asset\Asset;
use asset\StoredAssetCollection;

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

						$tmpThumbnail = null;

						// 1st attempt
						$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['square']['source_url'] ?? null;
						if ($tmpThumbnail === null) {
							Log::write("1st attempt failed", [$wpPost['link']], LogLevel::WARNING);
						}

						// 2nd attempt
						if ($tmpThumbnail === null) {
							$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
							if ($tmpThumbnail === null) {
								Log::write("2nd attempt failed", [$wpPost['link']], LogLevel::WARNING);
							}
						}

						// 3rd attempt
						if ($tmpThumbnail === null) {
							$tmpThumbnail = $wpPost['jetpack_featured_media_url'] ?? null;
							if ($tmpThumbnail === null) {
								Log::write("3rd attempt failed", [$wpPost['link']], LogLevel::WARNING);
							}
						}

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
