<?php

namespace creator\indexing;
// Location Textures

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

class CreatorIndexerLocationTextures extends CreatorIndexer
{

	protected Creator $creator = Creator::LOCATION_TEXTURES;
	private string $indexingBaseUrl = "https://locationtextures.com/panoramas/free-panoramas/?page=";
	private int $maxAssetsPerRound = 5;

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$page = 1;

		$processedAssets = 0;

		do {
			$rawHtml = Fetch::fetchRemoteData($this->indexingBaseUrl . $page);
			if ($rawHtml != "") {
				$dom = Html::domObjectFromHtmlString($rawHtml);
				$domQuery = new DomQuery($dom);

				$assetLinkElements = $domQuery->find("#product-category a.pack-link");
				$assetsFoundThisIteration = sizeof($assetLinkElements);

				foreach ($assetLinkElements as $assetLinkElement) {

					$assetImageElement = $assetLinkElement->find('img.pack-link-img');

					// use current date as a fallback
					$date =  date("Y-m-d");

					if (!in_array($assetLinkElement->attr('href'), $existingUrls)) {

						$detailPageRawHtml = Fetch::fetchRemoteData($assetLinkElement->attr('href'));
						$detailPageDom = Html::domObjectFromHtmlString($detailPageRawHtml);
						$detailPageDomQuery = new DomQuery($detailPageDom);

						// Find tags on detail page

						$tagLinks = $detailPageDomQuery->find("section a[href*='?tag']");
						$tags = [];
						foreach ($tagLinks as $tagLink) {
							$tags[] = $tagLink->text();
						}

						$tmpCollection->assets[] = new Asset(
							id: NULL,
							name: $assetImageElement->attr('title'),
							url: $assetLinkElement->attr('href'),
							thumbnailUrl: $assetImageElement->attr('data-src'),
							date: $date,
							tags: array_merge(
								array_filter(
									preg_split('/[^A-Za-z0-9]/', $assetImageElement->attr('title'))
								),
								$tags
							),
							type: Type::HDRI,
							license: License::CUSTOM,
							creator: $this->creator,
							quirks: [],
							status: AssetStatus::PENDING
						);

						$processedAssets += 1;
					}

					if ($processedAssets >= $this->maxAssetsPerRound) {
						break;
					}
				}
			} else {
				$assetsFoundThisIteration = 0;
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0 && $processedAssets < $this->maxAssetsPerRound);
		return $tmpCollection;
	}
}
