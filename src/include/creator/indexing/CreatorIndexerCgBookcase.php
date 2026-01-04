<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;

use creator\indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
use misc\Html;
use misc\Image;
use misc\StringUtil;
use Rct567\DomQuery\DomQuery;


class CreatorIndexerCgBookcase extends CreatorIndexer
{


	protected Creator $creator = Creator::CGBOOKCASE;
	private string $baseUrl = "https://www.cgbookcase.com/textures/";
	private int $maxAssets = 5;

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$dom = (new WebItemReference($this->baseUrl))->fetch()->parseAsDomDocument();
		$domQuery = new DomQuery($dom);

		$assetLinks = $domQuery->find('a[href*="/textures/"]');

		$urlArray = [];

		foreach ($assetLinks as $aL) {
			$urlArray[] = "https://www.cgbookcase.com" . $aL->href . "?source=3dassets.one";
		}

		$tmpCollection = new AssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!in_array($url, $existingUrls)) {

				$metaTags = (new WebItemReference($url))->fetch()->parseHtmlMetaTags();

				$tmpAsset = new Asset(
					id: NULL,
					name: $metaTags['tex1:name'],
					url: $url,
					date: new DateTime($metaTags['tex1:release-date']),
					tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags']),
					type: Type::fromTex1Tag($metaTags['tex1:type']),
					license: License::CC0,
					creator: Creator::CGBOOKCASE,
					thumbnailUrl: $metaTags['tex1:preview-image']
				);

				$tmpCollection->assets[] = $tmpAsset;

				$countAssets++;
				if ($countAssets >= $this->maxAssets) {
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
