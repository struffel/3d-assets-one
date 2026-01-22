<?php

namespace creator\logic;

use asset\Asset;
use asset\CommonLicense;
use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use Exception;
use creator\CreatorLogic;
use DateTime;
use fetch\WebItemReference;
use Rct567\DomQuery\DomQuery;

// poliigon

class CreatorLogicPoliigon extends CreatorLogic
{

	protected Creator $creator = Creator::POLIIGON;

	private string $baseUrl = "https://www.poliigon.com";
	private string $searchBaseUrl = "https://www.poliigon.com/search/free?page=";
	private array $urlTypeRegex = [
		'/\/texture\//i' => AssetType::PBR_MATERIAL,
		'/\/model\//i' => AssetType::MODEL_3D,
		'/\/hdri\//i' => AssetType::HDRI,
	];

	private function extractId($url)
	{
		return end(explode('/', rtrim($url, '/')));
	}

	private function isInExistingAssets($url, StoredAssetCollection $existingAssets)
	{
		// Extracting ID from the input link
		$id = $this->extractId($url);

		foreach ($existingAssets as $asset) {
			if ($this->extractId($asset->url) === $id) {
				return true;
			}
		}

		return false;
	}

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();

		$page = 1;

		do {
			$dom = (new WebItemReference($this->searchBaseUrl . $page))->fetch()->parseAsDomDocument();
			$domQuery = new DomQuery($dom);

			$assetBoxDomElements = $domQuery->find('a.asset-box');
			$assetBoxesFoundThisIteration = sizeof($assetBoxDomElements);
			foreach ($assetBoxDomElements as $assetBox) {

				$urlPath = $assetBox->attr('href');
				$url = $this->baseUrl . $urlPath;
				if (!$this->isInExistingAssets($url, $existingAssets)) {

					$name = $assetBox->find('img')->attr('alt');

					$type = NULL;

					foreach ($this->urlTypeRegex as $regex => $typeId) {
						if (preg_match($regex, $urlPath)) {
							$type = AssetType::from($typeId);
						}
					}
					if (!$type) {
						throw new Exception("Could not find type from urlPath '$urlPath'");
					}

					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $name,
						url: $url,
						date: new DateTime(),
						tags: preg_split('/\s|,/', $name),
						type: $type,

						creator: Creator::POLIIGON,
						status: ScrapedAssetStatus::NEWLY_FOUND,
						rawThumbnailData: new WebItemReference(
							url: $assetBox->find('img')->attr('src')
						)->fetch()->content
					);
				}
			}

			$page += 1;
		} while ($assetBoxesFoundThisIteration > 0 && $page < 20 /* Failsafe */);

		return $tmpCollection;
	}
}
