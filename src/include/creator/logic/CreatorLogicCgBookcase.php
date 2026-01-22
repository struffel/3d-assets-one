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

				$tmpAsset = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					title: $metaTags['tex1:name'],
					url: $url,
					date: new DateTime($metaTags['tex1:release-date']),
					tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags']),
					type: AssetType::fromTex1Tag($metaTags['tex1:type']),
					creator: Creator::CGBOOKCASE,
					rawThumbnailData: new WebItemReference(url: $metaTags['tex1:preview-image'])->fetch()->content,
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
		return Image::removeUniformBackground((new WebItemReference($url))->fetch()->content, 2, 2, 0.015);
	}
}
