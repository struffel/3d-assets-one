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

class CreatorLogicShareTextures extends CreatorLogic
{

	protected Creator $creator = Creator::SHARETEXTURES;
	protected int $maxAssetsPerRun = 10;

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
					title: $metaTags['og:title'] ?? throw new Exception("Could not resolve title from meta tags."),
					url: $url,
					type: AssetType::fromTex1Tag($metaTags['tex1:type'] ?? ""),
					creator: $this->creator,
					rawThumbnailData: new WebItemReference($metaTags['tex1:preview-image'])->fetch()->content,
					status: ScrapedAssetStatus::NEWLY_FOUND,
					tags: explode(",", $metaTags['tex1:tags'] ?? throw new Exception("Could not resolve tags from meta tags.")),
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
}
