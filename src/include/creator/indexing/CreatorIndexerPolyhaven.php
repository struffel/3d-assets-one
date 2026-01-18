<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;

use creator\indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
use log\Log;

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
		$result = new WebItemReference($this->apiUrl)->fetch()->parseAsJson();

		// Iterate through result
		foreach ($result as $key => $phAsset) {

			$url = $this->viewBaseUrl . $key;

			if (!in_array($url, $existingUrls)) {

				$date = new DateTime();
				$date->setTimestamp(($phAsset['date_added']));

				$tmpAsset = new Asset(
					id: NULL,
					url: $url,
					date: $date,
					name: $phAsset['name'],
					tags: $phAsset['tags'],
					thumbnailUrl: $this->thumbnailUrlPrefix . $key . $this->thumbnailUrlSuffix,
					type: Type::from($this->typeMapping[$phAsset['type']]),
					license: License::CC0,
					creator: Creator::POLYHAVEN,
					rawThumbnailData: new WebItemReference(
						url: $this->thumbnailUrlPrefix . $key . $this->thumbnailUrlSuffix
					)->fetch()->content
				);

				$tmpCollection->assets[] = $tmpAsset;
			}
		}

		return $tmpCollection;
	}
}
