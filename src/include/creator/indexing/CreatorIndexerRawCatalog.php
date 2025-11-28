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
use SimpleXMLElement;

// rawcatalog

class CreatorIndexerRawCatalog extends CreatorIndexer
{
	protected Creator $creator = Creator::RAWCATALOG;

	private string $apiUrl = "https://rawcatalog.com/freeset.xml";
	private int $maxAssetsPerRound = 100;
	private array $typeMatching = [
		"blueprints" => Type::OTHER,
		"materials" => Type::PBR_MATERIAL,
		"atlases" => Type::PBR_MATERIAL,
		"models" => Type::MODEL_3D
	];

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$targetUrl = $this->apiUrl;

		// Parse XML

		$sourceData = new SimpleXMLElement(Fetch::fetchRemoteData($targetUrl));

		$countAssets = 0;
		foreach ($this->typeMatching as $xPathPrefix => $type) {

			foreach ($sourceData->xpath("$xPathPrefix//file") as $rawCatalogAsset) {

				$url = $rawCatalogAsset->url;

				if ($countAssets < $this->maxAssetsPerRound && !in_array($url, $existingUrls)) {

					$tags = [];
					foreach ($rawCatalogAsset->tags->tag as $t) {
						$tags[] = $t;
					}

					$tmpAsset = new Asset(
						id: NULL,
						url: $rawCatalogAsset->url,
						name: $rawCatalogAsset->name,
						date: $rawCatalogAsset->updated,
						tags: $tags,
						type: $type,
						creator: Creator::RAWCATALOG,
						license: License::CUSTOM,
						thumbnailUrl: $rawCatalogAsset->cover,
						quirks: [Quirk::SIGNUP_REQUIRED]
					);

					$tmpCollection->assets[] = $tmpAsset;
					$countAssets++;
				}
			}
		}

		return $tmpCollection;
	}
}
