<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Fetch;
use indexing\CreatorIndexer;
use misc\Log;

class CreatorIndexerPolyhaven extends CreatorIndexer
{
	protected Creator $creator = Creator::POLYHAVEN;

	private string $apiUrl = "https://api.polyhaven.com/assets";
	private string $viewBaseUrl = "https://polyhaven.com/a/";
	private string $thumbnailUrlPrefix = "https://cdn.polyhaven.com/asset_img/thumbs/";
	private string $thumbnailUrlSuffix = ".png?height=512";
	private array $typeMapping = [
		"0" => Type::HDRI,
		"1" => Type::PBR_MATERIAL,
		"2" => Type::MODEL_3D
	];

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		// Prepare asset collection
		$tmpCollection = new AssetCollection();
		$result = Fetch::fetchRemoteJson($this->apiUrl);

		// Iterate through result
		foreach ($result as $key => $phAsset) {

			$url = $this->viewBaseUrl . $key;

			if (!in_array($url, $existingUrls)) {

				$tmpAsset = new Asset(
					id: NULL,
					url: $url,
					date: date('Y-m-d', $phAsset['date_published']),
					name: $phAsset['name'],
					tags: $phAsset['tags'],
					thumbnailUrl: $this->thumbnailUrlPrefix . $key . $this->thumbnailUrlSuffix,
					type: TYPE::from($this->typeMapping[$phAsset['type']]),
					license: License::CC0,
					creator: Creator::POLYHAVEN,
					quirks: [Quirk::ADS]
				);

				$tmpCollection->assets[] = $tmpAsset;
				Log::write("Found new asset: " . $tmpAsset->url);
			}
		}

		return $tmpCollection;
	}
}
