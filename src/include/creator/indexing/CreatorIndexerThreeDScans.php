<?php

namespace creator\indexing;
// Three D Scans

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;

use creator\indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
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
			$dom = new WebItemReference($this->indexingBaseUrl . $page)->fetch()->parseAsDomDocument();
			if ($dom != null) {
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
							date: new DateTime($date),
							tags: array_merge(array_filter(preg_split('/[^A-Za-z0-9]/', $assetLinkElement->attr('title'))), ['statue', 'sculpture']),
							type: Type::MODEL_3D,
							license: License::CC0,
							creator: $this->creator,
							quirks: [],
							status: AssetStatus::ACTIVE,
							rawThumbnailData: new WebItemReference(
								url: $assetImageElement->attr('src')
							)->fetch()->content
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
