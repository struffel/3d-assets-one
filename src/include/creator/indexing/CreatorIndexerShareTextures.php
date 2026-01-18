<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use asset\Quirk;

use creator\indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
use misc\Html;
use misc\Image;

class CreatorIndexerShareTextures extends CreatorIndexer
{

	protected Creator $creator = Creator::SHARETEXTURES;

	private string $listUrl = "https://www.sharetextures.com/tex1-list.php";
	private int $maxAssets = 5;

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		// Get list of URLs
		$urlArray = new WebItemReference($this->listUrl)->fetch()->parseAsCommaSeparatedList();

		$tmpCollection = new AssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!in_array($url, $existingUrls)) {
				$metaTags = new WebItemReference($url)->fetch()->parseHtmlMetaTags();

				$tmpAsset = new Asset(
					id: NULL,
					name: $metaTags['og:title'],
					url: $url,
					date: new DateTime($metaTags['tex1:release-date']),
					tags: explode(",", $metaTags['tex1:tags']),
					type: Type::fromTex1Tag($metaTags['tex1:type']),
					license: License::CC0,
					creator: Creator::SHARETEXTURES,
					thumbnailUrl: $metaTags['tex1:preview-image'],
					quirks: [Quirk::ADS],
					rawThumbnailData: $this->fetchThumbnailImage($metaTags['tex1:preview-image'])
				);

				$tmpCollection->assets[] = $tmpAsset;
				$countAssets++;
			}
			if ($countAssets >= $this->maxAssets) {
				break;
			}
		}

		return $tmpCollection;
	}

	public function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(new WebItemReference($url)->fetch()->content, 25, 25, 0.015);
	}
}
