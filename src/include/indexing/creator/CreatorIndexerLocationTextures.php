<?php

namespace indexing\creator;
// Location Textures

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;

use indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
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
			$dom = (new WebItemReference($this->indexingBaseUrl . $page))->fetch()->parseAsDomDocument();
			if ($dom != null) {

				$domQuery = new DomQuery($dom);

				$assetLinkElements = $domQuery->find("#product-category a.pack-link");
				$assetsFoundThisIteration = sizeof($assetLinkElements);

				foreach ($assetLinkElements as $assetLinkElement) {

					$assetImageElement = $assetLinkElement->find('img.pack-link-img');


					if (!in_array($assetLinkElement->attr('href'), $existingUrls)) {

						$detailPageDom = (new WebItemReference($assetLinkElement->attr('href')))->fetch()->parseAsDomDocument();
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
							date: new DateTime(),
							tags: array_merge(
								array_filter(
									preg_split('/[^A-Za-z0-9]/', $assetImageElement->attr('title'))
								),
								$tags
							),
							type: Type::HDRI,
							license: License::CUSTOM,
							creator: $this->creator,
							status: AssetStatus::ACTIVE,
							rawThumbnailData: new WebItemReference(
								url: $assetImageElement->attr('data-src')
							)->fetch()->content
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
