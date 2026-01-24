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
use log\Log;

// lightbeans

class CreatorLogicLightbeans extends CreatorLogic
{

	protected Creator $creator = Creator::LIGHTBEANS;

	protected int $maxAssetsPerRun = 20;

	private string $sitemapUrl = "https://lightbeans.com/sitemap.xml";
	private string $sitemapUrlMustContain = "lightbeans.com/en/texture/";
	private array $bannedTags = [
		"Lightbeans",
		"|",
		"-",
		"from"
	];

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		// Collect assets

		$tmpCollection = new ScrapedAssetCollection();

		$rawSitemapXml = (new WebItemReference($this->sitemapUrl))->fetch()->content;

		if ($rawSitemapXml) {

			$sitemap = simplexml_load_string($rawSitemapXml);
			$newUrls = [];

			foreach ($sitemap->url as $url) {
				if (!$existingAssets->containsUrl((string) $url->loc) && str_contains($url->loc, $this->sitemapUrlMustContain)) {
					$newUrls[] = (string) $url->loc;
				}
				if (sizeof($newUrls) >= $this->maxAssetsPerRun) {
					break;
				}
			}

			foreach ($newUrls as $newUrl) {

				$metatags = (new WebItemReference($newUrl))->fetch()->parseHtmlMetaTags();

				$thumbnailUrl = str_replace("dynamic-rectangle-image", "dynamic-square-image", $metatags['og:image'] ?? "");

				$title = $metatags['og:title'] ?? "";
				$title = str_replace("| Lightbeans", "", $title);

				$tags = explode(' ', $title);
				$tags = array_filter($tags, fn($tag) => !in_array($tag, $this->bannedTags));
				Log::write("Resolved tags ",  $tags);

				// Type
				$type = AssetType::PBR_MATERIAL;

				// Build asset
				$tmpCollection[] = new ScrapedAsset(
					id: NULL,
					creatorGivenId: null,
					title: $title,
					url: $newUrl,
					date: new DateTime(),
					tags: $tags,
					type: $type,

					creator: $this->creator,
					status: ScrapedAssetStatus::NEWLY_FOUND,
					rawThumbnailData: $this->fetchThumbnailImage($thumbnailUrl)
				);
			}
		}

		return $tmpCollection;
	}

	private function fetchThumbnailImage(string $url): string
	{

		// Load the image
		$imageData = (new WebItemReference($url))->fetch()->content;
		$image = imagecreatefromstring($imageData);

		// Get the dimensions of the original image
		$width = imagesx($image);
		$height = imagesy($image);

		// Calculate 65% of the smallest dimension to keep the crop square
		$cropSize = (int)(min($width, $height) * 0.65);

		// Calculate the coordinates for the (near-)center crop
		$x = (int)(($width - $cropSize) / 2);
		$y = (int)(($height - $cropSize) / 1.5);

		// Create a new image for the cropped result
		$croppedImage = imagecreatetruecolor($cropSize, $cropSize);

		// Crop the image to the calculated dimensions
		imagecopy($croppedImage, $image, 0, 0, $x, $y, $cropSize, $cropSize);

		// Output to string
		ob_start();
		imagepng($croppedImage);
		$result = ob_get_clean();

		return $result;
	}
}
