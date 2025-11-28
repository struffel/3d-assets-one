<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use Fetch;
use indexing\CreatorIndexer;

// hdri workshop

class CreatorIndexerHdriWorkshop extends CreatorIndexer
{
	protected Creator $creator = Creator::HDRIWORKSHOP;

	private string $apiUrl = "https://hdri-workshop.com/api";

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$apiOutput = Fetch::fetchRemoteJson($this->apiUrl);

		$tmpCollection = new AssetCollection();
		foreach ($apiOutput as $hdriWorkshopAsset) {
			if (!in_array($hdriWorkshopAsset['fullUrl'], $existingUrls)) {
				$tmpAsset = new Asset(
					id: NULL,
					name: $hdriWorkshopAsset['name'],
					url: $hdriWorkshopAsset['fullUrl'],
					tags: explode(" ", $hdriWorkshopAsset['name']),
					type: Type::HDRI,
					creator: Creator::HDRIWORKSHOP,
					license: License::CUSTOM,
					thumbnailUrl: $hdriWorkshopAsset['fullUrlThumb'],
					quirks: [],
					date: date("Y-m-d")
				);

				$tmpCollection->assets[] = $tmpAsset;
			}
		}

		return $tmpCollection;
	}
}
