<?php

namespace indexing\creator;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;

use indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;

// hdri workshop

class CreatorIndexerHdriWorkshop extends CreatorIndexer
{
	protected Creator $creator = Creator::HDRIWORKSHOP;

	private string $apiUrl = "https://hdri-workshop.com/api";

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$apiOutput = (new WebItemReference($this->apiUrl))->fetch()->parseAsJson();

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
					date: new DateTime(),
					rawThumbnailData: new WebItemReference(url: $hdriWorkshopAsset['fullUrlThumb'])->fetch()->content
				);

				$tmpCollection->assets[] = $tmpAsset;
			}
		}

		return $tmpCollection;
	}
}
