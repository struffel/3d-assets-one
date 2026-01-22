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

// hdri workshop

class CreatorLogicHdriWorkshop extends CreatorLogic
{
	protected Creator $creator = Creator::HDRIWORKSHOP;

	private string $apiUrl = "https://hdri-workshop.com/api";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$apiOutput = (new WebItemReference($this->apiUrl))->fetch()->parseAsJson();

		$tmpCollection = new ScrapedAssetCollection();
		foreach ($apiOutput as $hdriWorkshopAsset) {
			if (!$existingAssets->containsUrl($hdriWorkshopAsset['fullUrl'])) {
				$tmpAsset = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					title: $hdriWorkshopAsset['name'],
					url: $hdriWorkshopAsset['fullUrl'],
					tags: explode(" ", $hdriWorkshopAsset['name']),
					type: AssetType::HDRI,
					creator: Creator::HDRIWORKSHOP,
					date: new DateTime(),
					rawThumbnailData: new WebItemReference(url: $hdriWorkshopAsset['fullUrlThumb'])->fetch()->content,
					status: ScrapedAssetStatus::NEWLY_FOUND,
				);

				$tmpCollection[] = $tmpAsset;
			}
		}

		return $tmpCollection;
	}
}
