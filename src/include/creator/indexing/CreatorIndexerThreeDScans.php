<?php

namespace creator\indexing;
// Three D Scans

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use misc\Fetch;
use creator\indexing\CreatorIndexer;
use misc\Html;
use Rct567\DomQuery\DomQuery;

class CreatorIndexerThreeDScans extends CreatorIndexer
{

	

	protected Creator $creator = Creator::THREE_D_SCANS;
	private string $indexingBaseUrl = "https://threedscans.com/page/";

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$page = 1;

		do {
			$rawHtml = Fetch::fetchRemoteData($this->indexingBaseUrl . $page);
			if ($rawHtml != "") {
				$dom = Html::domObjectFromHtmlString($rawHtml);
				$domQuery = new DomQuery($dom);

				$assetLinkElements = $domQuery->find('article a');
				$assetsFoundThisIteration = sizeof($assetLinkElements);

				foreach ($assetLinkElements as $assetLinkElement) {

					$assetImageElement = $assetLinkElement->find('img.frontPageImg');

					// Extract year and month from thumbnail URL or use current date as a fallback
					preg_match('/[0-9]{4}\/[0-9]{2}/', $assetImageElement->attr('src'), $matches);
					$date = isset($matches[0]) ? str_replace('/', '-', $matches[0]) . "-01" : date("Y-m-d");

					if (!in_array($assetLinkElement->attr('href'), $existingUrls)) {
						$tmpCollection->assets[] = new Asset(
							id: NULL,
							name: $assetLinkElement->attr('title'),
							url: $assetLinkElement->attr('href'),
							thumbnailUrl: $assetImageElement->attr('src'),
							date: $date,
							tags: array_merge(array_filter(preg_split('/[^A-Za-z0-9]/', $assetLinkElement->attr('title'))), ['statue', 'sculpture']),
							type: Type::MODEL_3D,
							license: License::CC0,
							creator: $this->creator,
							quirks: [],
							status: AssetStatus::PENDING
						);
					}
				}
			} else {
				$assetsFoundThisIteration = 0;
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0);

		return $tmpCollection;
	}
}
