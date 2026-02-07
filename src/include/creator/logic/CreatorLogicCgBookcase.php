<?php

namespace creator\logic;


use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use DateTime;
use fetch\WebItemReference;
use thumbnail\Thumbnail;
use misc\StringUtil;
use Rct567\DomQuery\DomQuery;


class CreatorLogicCgBookcase extends CreatorLogic
{


	protected Creator $creator = Creator::CGBOOKCASE;
	protected int $maxAssetsPerRun = 5;

	private string $baseUrl = "https://www.cgbookcase.com/textures/";

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$dom = (new WebItemReference($this->baseUrl))->fetch()->parseAsDomDocument();
		$domQuery = new DomQuery($dom);

		$assetLinks = $domQuery->find('a[href*="/textures/"]');

		$urlArray = [];

		foreach ($assetLinks as $aL) {
			$urlArray[] = "https://www.cgbookcase.com" . $aL->href . "?source=3dassets.one";
		}

		$tmpCollection = new ScrapedAssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!$existingAssets->containsUrl($url)) {

				$metaTags = (new WebItemReference($url))->fetch()->parseHtmlMetaTags();

				if ($metaTags === null || !isset($metaTags['tex1:name'], $metaTags['tex1:release-date'], $metaTags['tex1:tags'], $metaTags['tex1:type'], $metaTags['tex1:preview-image'])) {
					continue;
				}

				$tmpAsset = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					title: $metaTags['tex1:name'],
					url: $url,
					tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags']),
					type: AssetType::fromTex1Tag($metaTags['tex1:type']),
					creator: Creator::CGBOOKCASE,
					rawThumbnail: new WebItemReference(url: $metaTags['tex1:preview-image'])->fetch()->parseAsGdImage(),
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
