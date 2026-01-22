<?php

namespace creator\logic;

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
use misc\Image;

class CreatorLogicShareTextures extends CreatorLogic
{

	protected Creator $creator = Creator::SHARETEXTURES;
	protected int $maxAssetsPerRun = 5;

	private string $listUrl = "https://www.sharetextures.com/tex1-list.php";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		// Get list of URLs
		$urlArray = new WebItemReference($this->listUrl)->fetch()->parseAsCommaSeparatedList();

		$tmpCollection = new ScrapedAssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!$existingAssets->containsUrl($url)) {
				$metaTags = new WebItemReference($url)->fetch()->parseHtmlMetaTags();

				$tmpAsset = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					title: $metaTags['og:title'],
					url: $url,
					date: new DateTime($metaTags['tex1:release-date']),
					tags: explode(",", $metaTags['tex1:tags']),
					type: AssetType::fromTex1Tag($metaTags['tex1:type']),
					creator: Creator::SHARETEXTURES,
					rawThumbnailData: $this->fetchThumbnailImage($metaTags['tex1:preview-image']),
					status: ScrapedAssetStatus::NEWLY_FOUND,
				);

				$tmpCollection[] = $tmpAsset;
				$countAssets++;
			}
			if ($countAssets >= $this->maxAssetsPerRun) {
				break;
			}
		}

		return $tmpCollection;
	}

	public function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(new WebItemReference($url)->fetch()->content, 25, 25, 0.015);
	}
}
