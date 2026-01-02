<?php

namespace creator\indexing;

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use asset\Quirk;
use Exception;

use creator\indexing\CreatorIndexer;
use fetch\WebItemReference;
use misc\Html;
use Rct567\DomQuery\DomQuery;

// poliigon

class CreatorIndexerPoliigon extends CreatorIndexer
{

	protected Creator $creator = Creator::POLIIGON;

	private string $baseUrl = "https://www.poliigon.com";
	private string $searchBaseUrl = "https://www.poliigon.com/search/free?page=";
	private array $urlTypeRegex = [
		'/\/texture\//i' => Type::PBR_MATERIAL,
		'/\/model\//i' => Type::MODEL_3D,
		'/\/hdri\//i' => Type::HDRI,
	];

	private function extractId($url)
	{
		return end(explode('/', rtrim($url, '/')));
	}

	private function isInExistingUrls($url, $existingUrls)
	{
		// Extracting ID from the input link

		$id = $this->extractId($url);
		$existingIds = [];
		foreach ($existingUrls as $eU) {
			$existingIds[] = $this->extractId($eU);
		}

		return in_array($id, $existingIds);
	}

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();

		$page = 1;

		do {
			$dom = (new WebItemReference($this->searchBaseUrl . $page))->fetch()->parseAsDomDocument();
			$domQuery = new DomQuery($dom);

			$assetBoxDomElements = $domQuery->find('a.asset-box');
			$assetBoxesFoundThisIteration = sizeof($assetBoxDomElements);
			foreach ($assetBoxDomElements as $assetBox) {

				$urlPath = $assetBox->attr('href');
				$url = $this->baseUrl . $urlPath;
				if (!$this->isInExistingUrls($url, $existingUrls)) {

					$name = $assetBox->find('img')->attr('alt');

					$type = NULL;

					foreach ($this->urlTypeRegex as $regex => $typeId) {
						if (preg_match($regex, $urlPath)) {
							$type = Type::from($typeId);
						}
					}
					if (!$type) {
						throw new Exception("Could not find type from urlPath '$urlPath'");
					}

					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $name,
						url: $url,
						thumbnailUrl: $assetBox->find('img')->attr('src'),
						date: date("Y-m-d"),
						tags: preg_split('/\s|,/', $name),
						type: $type,
						license: License::CUSTOM,
						creator: Creator::POLIIGON,
						quirks: [Quirk::SIGNUP_REQUIRED],
						status: AssetStatus::PENDING
					);
				}
			}

			$page += 1;
		} while ($assetBoxesFoundThisIteration > 0 && $page < 20 /* Failsafe */);

		return $tmpCollection;
	}
}
