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
use Exception;
use fetch\WebItemReference;
use thumbnail\Thumbnail;
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
					title: $metaTags['tex1:name'] ?? throw new Exception("Could not resolve title from meta tags."),
					url: $url,
					date: new DateTime($metaTags['tex1:release-date'] ?? date('Y-m-d')),
					tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags'] ?? throw new Exception("Could not resolve tags from meta tags.")),
					type: AssetType::fromTex1Tag($metaTags['tex1:type']),
					creator: Creator::TEXTURECAN,
					rawThumbnailData: new WebItemReference($metaTags['tex1:preview-image'])->fetch()->content,
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
}
