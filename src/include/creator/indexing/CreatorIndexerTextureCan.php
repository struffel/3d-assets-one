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
use misc\StringUtil;

class CreatorIndexerTextureCan extends CreatorIndexer
{

	protected Creator $creator = Creator::TEXTURECAN;

	private string $urlList = "https://www.texturecan.com/tex1-list.php";
	private int $maxAssets = 5;

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$urlArray = new WebItemReference($this->urlList)->fetch()->parseAsCommaSeparatedList();

		$tmpCollection = new AssetCollection();

		$countAssets = 0;
		foreach ($urlArray as $url) {
			if (!in_array($url, $existingUrls)) {
				$metaTags = new WebItemReference($url)->fetch()->parseHtmlMetaTags();

				$tmpAsset = new Asset(
					id: NULL,
					name: $metaTags['tex1:name'],
					url: $url,
					date: new DateTime($metaTags['tex1:release-date']),
					tags: StringUtil::explodeFilterTrim(",", $metaTags['tex1:tags']),
					type: Type::fromTex1Tag($metaTags['tex1:type']),
					license: License::CC0,
					creator: Creator::TEXTURECAN,
					thumbnailUrl: $metaTags['tex1:preview-image'],
					quirks: [Quirk::ADS],
					rawThumbnailData: $this->fetchThumbnailImage(
						$metaTags['tex1:preview-image']
					)
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
		return Image::removeUniformBackground(new WebItemReference($url)->fetch()->content, 2, 2, 0.015);
	}
}
