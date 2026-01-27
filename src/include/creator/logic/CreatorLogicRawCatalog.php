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
use Exception;
use fetch\WebItemReference;
use RuntimeException;

// rawcatalog

class CreatorLogicRawCatalog extends CreatorLogic
{
	protected Creator $creator = Creator::RAWCATALOG;
	protected int $maxAssetsPerRun = 25;

	private string $apiUrl = "https://rawcatalog.com/freeset.xml";

	/**
	 * 
	 * @var array<string,AssetType>
	 */
	private array $typeMatching = [
		"blueprints" => AssetType::OTHER,
		"materials" => AssetType::PBR_MATERIAL,
		"atlases" => AssetType::PBR_MATERIAL,
		"models" => AssetType::MODEL_3D
	];

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();
		$targetUrl = $this->apiUrl;

		// Parse XML

		$sourceData = new WebItemReference($targetUrl)->fetch()->parseAsSimpleXmlElement();

		if ($sourceData === null) {
			throw new RuntimeException("Could not fetch or parse RawCatalog source data from $targetUrl");
		}

		$countAssets = 0;
		foreach ($this->typeMatching as $xPathPrefix => $type) {

			$assetList = $sourceData->xpath("$xPathPrefix//file");

			if ($assetList === null || $assetList === false) {
				$assetList = [];
			}

			foreach ($assetList as $rawCatalogAsset) {
				$url = (string) $rawCatalogAsset->url;

				if ($countAssets < $this->maxAssetsPerRun && !$existingAssets->containsUrl($url)) {

					$tags = [];
					foreach ($rawCatalogAsset->tags->tag as $t) {
						$tags[] = (string) $t;
					}

					$tmpAsset = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						url: $url,
						title: (string) $rawCatalogAsset->name,
						date: new DateTime((string) $rawCatalogAsset->updated),
						tags: $tags,
						type: $type,
						creator: Creator::RAWCATALOG,

						rawThumbnailData: new WebItemReference(
							url: (string) $rawCatalogAsset->cover
						)->fetch()->content,
						status: ScrapedAssetStatus::NEWLY_FOUND,
					);

					$tmpCollection[] = $tmpAsset;
					$countAssets++;
				}
			}
		}

		return $tmpCollection;
	}
}
