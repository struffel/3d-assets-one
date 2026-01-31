<?php

namespace creator\logic;

use asset\StoredAsset;
use asset\CommonLicense;
use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use DateTime;
use Exception;
use fetch\WebItemReference;
use log\Log;

class CreatorLogicAmbientCg extends CreatorLogic
{

	protected Creator $creator = Creator::AMBIENTCG;
	private string $apiUrl = "https://ambientcg.com/api/v2/full_json";
	/** @var array<string, int|string> */
	private array $initialParameters = [
		"limit" => 100,
		"offset" => 0,
		"include" => "displayData,tagData,imageData"
	];

	private int $maxAssetsPerRun = 25;

	/** @var array<string, AssetType> */
	private array $typeMapping = [
		"Material" => AssetType::PBR_MATERIAL,
		"Decal" => AssetType::PBR_MATERIAL,
		"Atlas" => AssetType::PBR_MATERIAL,
		"HDRI" => AssetType::HDRI,
		"3DModel" => AssetType::MODEL_3D,
		"SculptingBrush" => AssetType::OTHER,
		"Terrain" => AssetType::OTHER,
		"SBSAR" => AssetType::PBR_MATERIAL,
		"Substance" => AssetType::PBR_MATERIAL,
		"PlainTexture" => AssetType::PBR_MATERIAL,
		"Brush" => AssetType::OTHER,
		"HDRIElement" => AssetType::HDRI
	];

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{
		// Contact API and get new assets
		$targetUrl = $this->apiUrl . "?" . http_build_query($this->initialParameters);

		// Prepare asset collection
		$newAssets = new ScrapedAssetCollection();

		while ($targetUrl != "" && sizeof($newAssets) < $this->maxAssetsPerRun) {
			$result = new WebItemReference($targetUrl)->fetch()->parseAsJson();

			if ($result === null || !isset($result['foundAssets'])) {
				break;
			}

			// Iterate through result
			foreach ($result['foundAssets'] as $acgAsset) {

				if (!$existingAssets->containsUrl(strtolower($acgAsset['shortLink'])) && sizeof($newAssets) < $this->maxAssetsPerRun) {

					$tmpAsset = new ScrapedAsset(
						url: $acgAsset['shortLink'],
						creatorGivenId: $acgAsset['assetId'] ?? NULL,
						title: $acgAsset['displayName'],
						tags: $acgAsset['tags'],
						type: $this->typeMapping[$acgAsset['dataType']] ?? AssetType::OTHER,

						creator: Creator::AMBIENTCG,
						id: NULL,
						rawThumbnailData: new WebItemReference(url: $acgAsset['previewImage']['512-PNG'])->fetch()->content,
						status: ScrapedAssetStatus::NEWLY_FOUND,
					);

					$newAssets[] = $tmpAsset;
				}
			}

			$targetUrl = $result['nextPageHttp'] ?? "";
		}

		return $newAssets;
	}
}
