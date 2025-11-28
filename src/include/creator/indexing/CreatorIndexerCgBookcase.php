<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use misc\Fetch;
use creator\indexing\CreatorIndexer;
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

		$rawHtml = Fetch::fetchRemoteData($this->baseUrl);

		$dom = Html::domObjectFromHtmlString($rawHtml);
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

				$siteContent = Fetch::fetchRemoteData($url);
				$metaTags = Html::readMetatagsFromHtmlString($siteContent);

				$tmpAsset = new Asset(
					id: NULL,
					name: $metaTags['tex1:name'],
					url: $url,
					date: $metaTags['tex1:release-date'],
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
		return Image::removeUniformBackground(Fetch::fetchRemoteData($url), 2, 2, 0.015);
	}
}
