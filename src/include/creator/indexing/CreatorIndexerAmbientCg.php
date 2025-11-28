<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use asset\Quirk;
use misc\Fetch;
use creator\indexing\CreatorIndexer;
use misc\Log;

class CreatorIndexerAmbientCg extends CreatorIndexer
{

	protected Creator $creator = Creator::AMBIENTCG;
	private string $apiUrl = "https://ambientcg.com/api/v2/full_json";

	private array $typeMapping = [
		"Material" => Type::PBR_MATERIAL,
		"Decal" => Type::PBR_MATERIAL,
		"Atlas" => Type::PBR_MATERIAL,
		"HDRI" => Type::HDRI,
		"3DModel" => Type::MODEL_3D,
		"SculptingBrush" => Type::OTHER,
		"Terrain" => Type::OTHER,
		"SBSAR" => Type::PBR_MATERIAL,
		"Substance" => Type::PBR_MATERIAL,
		"PlainTexture" => Type::PBR_MATERIAL,
		"Brush" => Type::OTHER,
		"HDRIElement" => Type::HDRI
	];

	public function findNewAssets(array $existingUrls): AssetCollection
	{
		Log::stepIn(__FUNCTION__);
		Log::write("Start looking for new assets");

		// Contact API and get new assets
		$initialParameters = [
			"limit" => 100,
			"offset" => 0,
			"include" => "displayData,tagData,imageData"
		];

		$existingUrls = array_map(fn($u) => strtolower($u), $existingUrls);

		$targetUrl = $this->apiUrl . "?" . http_build_query($initialParameters);

		// Prepare asset collection
		$tmpCollection = new AssetCollection();

		while ($targetUrl != "") {
			$result = Fetch::fetchRemoteJson($targetUrl);

			// Iterate through result
			foreach ($result['foundAssets'] as $acgAsset) {

				if (!in_array(strtolower($acgAsset['shortLink']), $existingUrls)) {

					$tmpAsset = new Asset(
						url: $acgAsset['shortLink'],
						thumbnailUrl: $acgAsset['previewImage']['512-PNG'],
						date: $acgAsset['releaseDate'],
						name: $acgAsset['displayName'],
						tags: $acgAsset['tags'],
						type: Type::from($this->typeMapping[$acgAsset['dataType']]),
						license: License::CC0,
						creator: Creator::AMBIENTCG,
						id: NULL,
						quirks: [Quirk::ADS]
					);

					$tmpCollection->assets[] = $tmpAsset;
					Log::write("Found new asset: " . $tmpAsset->url);
				}
			}

			$targetUrl = $result['nextPageHttp'] ?? "";
		}
		Log::stepOut(__FUNCTION__);
		return $tmpCollection;
	}
}
