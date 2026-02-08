<?php

namespace creator\logic;
// Location Textures


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

class CreatorLogicLocationTextures extends CreatorLogic
{

	protected Creator $creator = Creator::LOCATION_TEXTURES;
	protected int $maxAssetsPerRun = 5;

	private string $indexingBaseUrl = "https://locationtextures.com/panoramas/free-panoramas/?page=";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();
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


					if (!$existingAssets->containsUrl($assetLinkElement->attr('href'))) {

						$detailPageDom = (new WebItemReference($assetLinkElement->attr('href')))->fetch()->parseAsDomDocument();
						$detailPageDomQuery = new DomQuery($detailPageDom);

						// Find tags on detail page

						$tagLinks = $detailPageDomQuery->find("section a[href*='?tag']");
						$tags = [];
						foreach ($tagLinks as $tagLink) {
							$tags[] = $tagLink->text();
						}

						$tmpCollection[] = new ScrapedAsset(
							id: NULL,
							creatorGivenId: null,
							title: $assetImageElement->attr('title'),
							url: $assetLinkElement->attr('href'),
							tags: array_merge(
								array_filter(
									preg_split('/[^A-Za-z0-9]/', $assetImageElement->attr('title')) ?: []
								),
								$tags
							),
							type: AssetType::HDRI,

							creator: $this->creator,
							status: ScrapedAssetStatus::NEWLY_FOUND,
							rawThumbnail: new WebItemReference(
								url: $assetImageElement->attr('data-src')
							)->fetch()->parseAsGdImage()
						);

						$processedAssets += 1;
					}

					if ($processedAssets >= $this->maxAssetsPerRun) {
						break;
					}
				}
			} else {
				$assetsFoundThisIteration = 0;
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0 && $processedAssets < $this->maxAssetsPerRun);
		return $tmpCollection;
	}
}
