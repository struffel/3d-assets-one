<?php

namespace creator\impl;
// Location Textures

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use Fetch;
use indexing\CreatorIndexer;
use misc\Html;
use Rct567\DomQuery\DomQuery;

class CreatorIndexerLocationTextures extends CreatorIndexer
{

	protected static Creator $creator = Creator::LOCATION_TEXTURES;
	private static string $indexingBaseUrl = "https://locationtextures.com/panoramas/free-panoramas/?page=";
	private static int $maxAssetsPerRound = 5;

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$page = 1;

		$processedAssets = 0;

		do {
			$rawHtml = Fetch::fetchRemoteData(self::$indexingBaseUrl . $page);
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
							creator: self::$creator,
							quirks: [],
							status: AssetStatus::PENDING
						);

						$processedAssets += 1;
					}

					if ($processedAssets >= self::$maxAssetsPerRound) {
						break;
					}
				}
			} else {
				$assetsFoundThisIteration = 0;
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0 && $processedAssets < self::$maxAssetsPerRound);
		return $tmpCollection;
	}
}
