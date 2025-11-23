<?php

namespace creator\impl;

use asset\Asset;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Fetch;
use indexing\CreatorIndexer;
use misc\Html;
use misc\Image;

class CreatorIndexerShareTextures extends CreatorIndexer
{
	private static string $listUrl = "https://www.sharetextures.com/tex1-list.php";
	private static int $maxAssets = 5;

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		// Get list of URLs
		$urlArray = Fetch::fetchRemoteCommaSeparatedList(self::$listUrl);

		$tmpCollection = new AssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!in_array($url, $existingUrls)) {
				$siteContent = Fetch::fetchRemoteData($url);
				$metaTags = Html::readMetatagsFromHtmlString($siteContent);

				$tmpAsset = new Asset(
					id: NULL,
					name: $metaTags['og:title'],
					url: $url,
					date: $metaTags['tex1:release-date'],
					tags: explode(",", $metaTags['tex1:tags']),
					type: Type::fromTex1Tag($metaTags['tex1:type']),
					license: License::CC0,
					creator: Creator::SHARETEXTURES,
					thumbnailUrl: $metaTags['tex1:preview-image'],
					quirks: [Quirk::ADS]
				);

				$tmpCollection->assets[] = $tmpAsset;
				$countAssets++;
			}
			if ($countAssets >= self::$maxAssets) {
				break;
			}
		}

		return $tmpCollection;
	}

	public static function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(Fetch::fetchRemoteData($url), 25, 25, 0.015);
	}
}
