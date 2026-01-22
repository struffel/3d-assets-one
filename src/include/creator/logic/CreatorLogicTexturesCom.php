<?php

namespace creator\logic;

use asset\CommonLicense;
use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use DateTime;
use fetch\WebItemReference;

// textures.com

class CreatorLogicTexturesCom extends CreatorLogic
{

	protected Creator $creator = Creator::TEXTURES_COM;

	private string $apiBaseUrl = "https://www.textures.com/api/v1/texture/search?filter=free&page=";
	private array $categoryMapping = [
		"114553" => AssetType::MODEL_3D,
		"114561" => AssetType::OTHER,
		"114548" => AssetType::PBR_MATERIAL,
		"114563" => AssetType::PBR_MATERIAL,
		"114570" => AssetType::MODEL_3D,
		"114558" => AssetType::PBR_MATERIAL,
		"114557" => AssetType::OTHER,
		"114552" => AssetType::HDRI,
		"23740" => AssetType::HDRI,
		"114568" => AssetType::OTHER,
		"114571" => AssetType::OTHER
	];

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();

		$page = 1;

		do {
			$apiData = new WebItemReference($this->apiBaseUrl . $page)->fetch()->parseAsJson();

			$assetsFoundThisIteration = sizeof($apiData['data']);
			foreach ($apiData['data'] as $texComAsset) {

				$url = "https://textures.com/download/" . $texComAsset['filenameWithoutSet'] . "/" . $texComAsset['defaultPhotoSet']['id'];

				if (!$existingAssets->containsUrl($url)) {

					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $texComAsset['defaultPhotoSet']['titleThumbnail'],
						url: $url,
						date: new DateTime($texComAsset['defaultPhotoSet']['createdAtUtc']),
						tags: array_filter(preg_split('/[^A-Za-z0-9]/', $texComAsset['defaultPhotoSet']['titleThumbnail'])),
						type: $this->categoryMapping[$texComAsset['defaultCategoryId']] ?? AssetType::OTHER,
						creator: Creator::TEXTURES_COM,
						status: ScrapedAssetStatus::NEWLY_FOUND,
						rawThumbnailData: new WebItemReference(
							url: "https://textures.com/" . $texComAsset['picture']
						)->fetch()->content
					);
				}
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0 && $page < 20 /* Failsafe */);

		return $tmpCollection;
	}
}
