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
use misc\StringUtil;

class CreatorLogicTextureCan extends CreatorLogic
{

	protected Creator $creator = Creator::TEXTURECAN;
	protected int $maxAssetsPerRun = 5;

	private string $urlList = "https://www.texturecan.com/tex1-list.php";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$urlArray = new WebItemReference($this->urlList)->fetch()->parseAsCommaSeparatedList();

		$tmpCollection = new ScrapedAssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!$existingAssets->containsUrl($url)) {
				$metaTags = new WebItemReference($url)->fetch()->parseHtmlMetaTags();

				$tmpAsset = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					title: $metaTags['tex1:name'],
					url: $url,
					date: new DateTime($metaTags['tex1:release-date']),
					tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags']),
					type: AssetType::fromTex1Tag($metaTags['tex1:type']),
					creator: Creator::TEXTURECAN,
					rawThumbnailData: $this->fetchThumbnailImage(
						$metaTags['tex1:preview-image']
					),
					status: ScrapedAssetStatus::NEWLY_FOUND,
				);

				$tmpCollection[] = $tmpAsset;

				$countAssets++;
				if ($countAssets >= $this->maxAssetsPerRun) {
					break;
				}
			}
		}

		return $tmpCollection;
	}

	public function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(new WebItemReference($url)->fetch()->content, 2, 2, 0.015);
	}
}
