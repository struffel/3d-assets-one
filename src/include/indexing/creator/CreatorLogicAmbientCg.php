<?php

namespace indexing\creator;

use asset\Asset;
use asset\CommonLicense;
use asset\AssetType;
use asset\StoredAssetCollection;
use creator\Creator;

use indexing\CreatorLogic;
use DateTime;
use fetch\WebItemReference;
use log\Log;

class CreatorLogicAmbientCg extends CreatorLogic
{

	protected Creator $creator = Creator::AMBIENTCG;
	private string $apiUrl = "https://ambientcg.com/api/v2/full_json";

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

	public function scrapeAssets(array $existingUrls): StoredAssetCollection
	{

		// Contact API and get new assets
		$initialParameters = [
			"limit" => 100,
			"offset" => 0,
			"include" => "displayData,tagData,imageData"
		];

		$existingUrls = array_map(fn($u) => strtolower($u), $existingUrls);

		$targetUrl = $this->apiUrl . "?" . http_build_query($initialParameters);

		// Prepare asset collection
		$tmpCollection = new StoredAssetCollection();

		while ($targetUrl != "" && sizeof($tmpCollection->assets) < $this->maxAssetsPerRun) {
			$result = new WebItemReference($targetUrl)->fetch()->parseAsJson();

			// Iterate through result
			foreach ($result['foundAssets'] as $acgAsset) {

				if (!in_array(strtolower($acgAsset['shortLink']), $existingUrls) && sizeof($tmpCollection->assets) < $this->maxAssetsPerRun) {

					$tmpAsset = new Asset(
						url: $acgAsset['shortLink'],
						thumbnailUrl: $acgAsset['previewImage']['512-PNG'],
						date: new DateTime($acgAsset['releaseDate']),
						title: $acgAsset['displayName'],
						tags: $acgAsset['tags'],
						type: $this->typeMapping[$acgAsset['dataType']] ?? AssetType::OTHER,
						license: CommonLicense::CC0,
						creator: Creator::AMBIENTCG,
						id: NULL,
						rawThumbnailData: new WebItemReference(url: $acgAsset['previewImage']['512-PNG'])->fetch()->content
					);

					$tmpCollection->assets[] = $tmpAsset;
				}
			}

			$targetUrl = $result['nextPageHttp'] ?? "";
		}

		return $tmpCollection;
	}
}
