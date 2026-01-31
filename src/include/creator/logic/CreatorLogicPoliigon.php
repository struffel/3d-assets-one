<?php

namespace creator\logic;

use asset\Asset;

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
use RuntimeException;

class CreatorLogicPoliigon extends CreatorLogic
{

	protected Creator $creator = Creator::POLIIGON;

	private string $baseUrl = "https://www.poliigon.com";
	private string $searchBaseUrl = "https://www.poliigon.com/free?sort=newest&page=";
	/** @var array<string, AssetType> */
	private array $urlTypeRegex = [
		'/\/texture\//i' => AssetType::PBR_MATERIAL,
		'/\/model\//i' => AssetType::MODEL_3D,
		'/\/hdri\//i' => AssetType::HDRI,
	];

	protected int $maxAssetsPerRun = 100;

	private function extractId(string $url): string
	{
		$parts = explode('/', rtrim($url, '/'));
		return end($parts) ?: '';
	}

	private function isInExistingAssets(string $url, StoredAssetCollection $existingAssets): bool
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

			// Find all "asset boxes", which are the divs that contain the rest
			$assetBoxDomElements = $domQuery->find('div.asset-box__item-inner');
			$assetBoxesFoundThisIteration = sizeof($assetBoxDomElements);

			foreach ($assetBoxDomElements as $assetBox) {

				if (sizeof($tmpCollection) >= $this->maxAssetsPerRun) {
					break 2; // Break out of both loops
				}

				// Find the URL of the asset
				$assetLink = $assetBox->find('a.asset-box__item-link');
				$urlPath = $assetLink->attr('href');
				$url = $this->baseUrl . $urlPath;

				// Check if already exists
				if (!$this->isInExistingAssets($url, $existingAssets)) {

					// Get the name
					$name = $assetBox->find('.asset-box__item-title-name')->text();

					// Determine the type
					$type = NULL;
					foreach ($this->urlTypeRegex as $regex => $typeCandidate) {
						if (preg_match($regex, $urlPath)) {
							$type = $typeCandidate;
						}
					}
					if (!$type) {
						throw new Exception("Could not find type from urlPath '$urlPath'");
					}

					$tags = preg_split('/\s|,/', $name) ?: throw new RuntimeException("Failed to split tags from name '$name'");
					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $name,
						url: $url,
						date: new DateTime(),
						tags: $tags,
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
