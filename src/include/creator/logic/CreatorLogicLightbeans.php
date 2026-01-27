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
use RuntimeException;

// lightbeans

class CreatorLogicLightbeans extends CreatorLogic
{

	protected Creator $creator = Creator::LIGHTBEANS;

	protected int $maxAssetsPerRun = 20;

	private string $sitemapUrl = "https://lightbeans.com/sitemap.xml";
	private string $sitemapUrlMustContain = "lightbeans.com/en/texture/";

	/**
	 * 
	 * @var array<string>
	 */
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

		$sitemap = (new WebItemReference($this->sitemapUrl))->fetch()->parseAsSitemap();

		if ($sitemap) {

			$newUrls = [];

			foreach ($sitemap as $site) {
				if (!$existingAssets->containsUrl((string) $site->url) && str_contains($site->url, $this->sitemapUrlMustContain)) {
					$newUrls[] = (string) $site->url;
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

		if ($imageData === null) {
			throw new RuntimeException("Failed to fetch image from URL: " . $url);
		}

		$image = imagecreatefromstring($imageData);

		if ($image === false) {
			throw new RuntimeException("Failed to load image from fetched data.");
		}

		// Get the dimensions of the original image
		$width = imagesx($image);
		$height = imagesy($image);

		// Calculate 65% of the smallest dimension to keep the crop square
		/** @var int<1, max> $cropSize */
		$cropSize = (int)max(min($width, $height) * 0.65, 1);

		// Calculate the coordinates for the (near-)center crop
		$x = (int) (($width - $cropSize) / 2);
		$y = (int) (($height - $cropSize) / 1.5);

		// Create a new image for the cropped result
		$croppedImage = imagecreatetruecolor($cropSize, $cropSize);

		// Crop the image to the calculated dimensions
		imagecopy($croppedImage, $image, 0, 0, $x, $y, $cropSize, $cropSize);

		// Output to string
		ob_start();
		imagepng($croppedImage);
		$result = ob_get_clean();

		if ($result === false) {
			throw new RuntimeException("Failed to capture cropped image output.");
		}

		return $result;
	}
}
