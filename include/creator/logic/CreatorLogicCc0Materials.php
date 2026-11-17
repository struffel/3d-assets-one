<?php

namespace creator\logic;

use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use fetch\WebItemReference;
use misc\StringUtil;

// CC0 Materials: every material page carries tex1 meta tags (the same format as CG Bookcase),
// and the sitemap lists every published material.

class CreatorLogicCc0Materials extends CreatorLogic
{

	protected Creator $creator = Creator::CC0MATERIALS;
	protected int $maxAssetsPerRun = 10;

	private string $sitemapUrl = "https://cc0materials.com/sitemap.xml";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{
		$tmpCollection = new ScrapedAssetCollection();

		$pages = (new WebItemReference($this->sitemapUrl))->fetch()->parseAsSitemap() ?? [];

		$countAssets = 0;
		foreach ($pages as $page) {
			if (!str_contains($page->url, "/materials/")) {
				continue;
			}

			$url = $page->url . "?source=3dassets.one";
			if ($existingAssets->containsUrl($url)) {
				continue;
			}

			$metaTags = (new WebItemReference($page->url))->fetch()->parseHtmlMetaTags();

			if ($metaTags === null || !isset($metaTags['tex1:name'], $metaTags['tex1:tags'], $metaTags['tex1:type'], $metaTags['tex1:preview-image'])) {
				continue;
			}

			$tmpCollection[] = new ScrapedAsset(
				id: NULL,
				creatorGivenId: null,
				title: $metaTags['tex1:name'],
				url: $url,
				tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags']),
				type: AssetType::fromTex1Tag($metaTags['tex1:type']),
				creator: Creator::CC0MATERIALS,
				rawThumbnail: new WebItemReference(url: $metaTags['tex1:preview-image'])->fetch()->parseAsGdImage(),
				status: ScrapedAssetStatus::NEWLY_FOUND,
			);

			$countAssets++;
			if ($countAssets >= $this->maxAssetsPerRun) {
				break;
			}
		}

		return $tmpCollection;
	}
}
