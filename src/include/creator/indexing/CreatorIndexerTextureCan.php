<?php

namespace creator\indexing;

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
use misc\Strings;

class CreatorFetcher6 extends CreatorIndexer
{

	protected static Creator $creator = Creator::TEXTURECAN;

	private static string $urlList = "https://www.texturecan.com/tex1-list.php";
	private static int $maxAssets = 5;

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		$urlArray = Fetch::fetchRemoteCommaSeparatedList(self::$urlList);

		$tmpCollection = new AssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!in_array($url, $existingUrls)) {
				$metaTags = Html::readMetatagsFromHtmlString(Fetch::fetchRemoteData($url));

				$tmpAsset = new Asset(
					id: NULL,
					name: $metaTags['tex1:name'],
					url: $url,
					date: $metaTags['tex1:release-date'],
					tags: Strings::explodeFilterTrim(",", $metaTags['tex1:tags']),
					type: Type::fromTex1Tag($metaTags['tex1:type']),
					license: License::CC0,
					creator: Creator::TEXTURECAN,
					thumbnailUrl: $metaTags['tex1:preview-image'],
					quirks: [Quirk::ADS]
				);

				$tmpCollection->assets[] = $tmpAsset;

				$countAssets++;
				if ($countAssets >= self::$maxAssets) {
					break;
				}
			}
		}

		return $tmpCollection;
	}

	public static function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(Fetch::fetchRemoteData($url), 2, 2, 0.015);
	}
}
