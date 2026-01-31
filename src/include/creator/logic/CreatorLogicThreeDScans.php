<?php

namespace creator\logic;
// Three D Scans

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
use Rct567\DomQuery\DomQuery;

class CreatorLogicThreeDScans extends CreatorLogic
{

	protected Creator $creator = Creator::THREE_D_SCANS;
	private string $indexingBaseUrl = "https://threedscans.com/page/";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();
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

					if (!$existingAssets->containsUrl($assetLinkElement->attr('href'))) {
						$tmpCollection[] = new ScrapedAsset(
							id: NULL,
							creatorGivenId: null,
							title: $assetLinkElement->attr('title'),
							url: $assetLinkElement->attr('href'),
							tags: array_merge(
								array_filter(
									preg_split(
										'/[^A-Za-z0-9]/',
										$assetLinkElement->attr('title')
									) ?: []
								),
								['statue', 'sculpture']
							),
							type: AssetType::MODEL_3D,
							creator: $this->creator,
							status: ScrapedAssetStatus::NEWLY_FOUND,
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
