<?php

namespace creator\indexing;

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use asset\Quirk;
use misc\Fetch;
use creator\indexing\CreatorIndexer;
use misc\Html;
use misc\Log;

// lightbeans

class CreatorIndexerLightbeans extends CreatorIndexer
{

	protected Creator $creator = Creator::LIGHTBEANS;

	private string $sitemapUrl = "https://lightbeans.com/sitemap.xml";
	private string $sitemapUrlMustContain = "lightbeans.com/en/texture/";
	private int $maxPerIteration = 10;
	private array $bannedTags = [
		"Lightbeans",
		"|",
		"-",
		"from"
	];

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		// Collect assets

		$tmpCollection = new AssetCollection();

		$rawSitemapXml = Fetch::fetchRemoteData(
			url: $this->sitemapUrl
		);

		if ($rawSitemapXml) {

			$sitemap = simplexml_load_string($rawSitemapXml);
			$newUrls = [];

			foreach ($sitemap->url as $url) {
				if (!in_array($url->loc, $existingUrls) && str_contains($url->loc, $this->sitemapUrlMustContain)) {
					$newUrls[] = (string) $url->loc;
				}
				if (sizeof($newUrls) >= $this->maxPerIteration) {
					break;
				}
			}

			foreach ($newUrls as $newUrl) {

				$html = Fetch::fetchRemoteData($newUrl);
				$dom = Html::domObjectFromHtmlString($html);
				$metatags = Html::readMetatagsFromHtmlString($html);

				$thumbnailUrl = str_replace("dynamic-rectangle-image", "dynamic-square-image", $metatags['og:image'] ?? "");

				$title = $metatags['og:title'] ?? "";
				$title = str_replace("| Lightbeans", "", $title);

				$tags = explode(' ', $title);
				$tags = array_filter($tags, fn($tag) => !in_array($tag, $this->bannedTags));
				Log::write("Resolved tags: " . implode(',', $tags));

				// Type
				$type = Type::PBR_MATERIAL;

				// Date
				$date = date("Y-m-d");

				// Build asset
				$tmpCollection->assets[] = new Asset(
					id: NULL,
					name: $title,
					url: $newUrl,
					thumbnailUrl: $thumbnailUrl,
					date: $date,
					tags: $tags,
					type: $type,
					license: License::CUSTOM,
					creator: $this->creator,
					quirks: [
						Quirk::SIGNUP_REQUIRED
					],
					status: AssetStatus::PENDING
				);
			}
		}

		return $tmpCollection;
	}

	public function fetchThumbnailImage(string $url): string
	{

		// Load the image
		$image = Fetch::fetchRemoteData($url);
		$imagick = new \Imagick();
		$imagick->readImageBlob($image);

		//Get the dimensions of the original image
		$width = $imagick->getImageWidth();
		$height = $imagick->getImageHeight();

		// Calculate 60% of the smallest dimension to keep the crop square
		$cropSize = min($width, $height) * 0.75;

		// Calculate the coordinates for the center crop
		$x = ($width - $cropSize) / 2;
		$y = ($height - $cropSize) / 2;

		// Crop the image to the calculated dimensions
		$imagick->cropImage($cropSize, $cropSize, $x, $y);


		return $imagick->getImageBlob();
	}
}
