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
use RuntimeException;

class CreatorLogicPolyhaven extends CreatorLogic
{
	protected Creator $creator = Creator::POLYHAVEN;

	private string $apiUrl = "https://api.polyhaven.com/assets";
	private string $viewBaseUrl = "https://polyhaven.com/a/";
	private string $thumbnailUrlPrefix = "https://cdn.polyhaven.com/asset_img/thumbs/";
	private string $thumbnailUrlSuffix = ".png?height=512";

	/**
	 * 
	 * @var array<int,AssetType>
	 */
	private array $typeMapping = [
		0 => AssetType::HDRI,
		1 => AssetType::PBR_MATERIAL,
		2 => AssetType::MODEL_3D
	];

	private int $maxAssetsPerRun = 10;

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		// Prepare asset collection
		$tmpCollection = new ScrapedAssetCollection();
		$result = new WebItemReference($this->apiUrl)->fetch()->parseAsJson();

		if ($result === null) {
			throw new RuntimeException("Could not fetch or parse Polyhaven API data from " . $this->apiUrl);
		}

		// Iterate through result
		foreach ($result as $key => $phAsset) {

			$url = $this->viewBaseUrl . $key;

			if (!$existingAssets->containsUrl($url) && sizeof($tmpCollection) < $this->maxAssetsPerRun) {

				$date = new DateTime();
				$date->setTimestamp(($phAsset['date_added']));

				$tmpAsset = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					url: $url,
					title: $phAsset['name'],
					tags: $phAsset['tags'],
					type: $this->typeMapping[intval($phAsset['type'])] ?? AssetType::OTHER,

					creator: Creator::POLYHAVEN,
					rawThumbnailData: new WebItemReference(
						url: $this->thumbnailUrlPrefix . $key . $this->thumbnailUrlSuffix
					)->fetch()->content,
					status: ScrapedAssetStatus::NEWLY_FOUND,
				);

				$tmpCollection[] = $tmpAsset;
			}
		}

		return $tmpCollection;
	}
}
