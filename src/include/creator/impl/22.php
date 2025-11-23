<?php

// lightbeans

class CreatorFetcher22 extends CreatorFetcher
{

	public CREATOR $creator = CREATOR::LIGHTBEANS;

	function findNewAssets(array $existingUrls, array $config): AssetCollection
	{

		// Collect assets

		$tmpCollection = new AssetCollection();

		$rawSitemapXml = FetchLogic::fetchRemoteData(
			url: $config['sitemapUrl']
		);

		if ($rawSitemapXml) {

			$sitemap = simplexml_load_string($rawSitemapXml);
			$newUrls = [];

			foreach ($sitemap->url as $url) {
				if (!in_array($url->loc, $existingUrls) && str_contains($url->loc, $config['sitemapUrlMustContain'])) {
					$newUrls[] = (string) $url->loc;
				}
				if (sizeof($newUrls) >= $config['maxPerIteration']) {
					break;
				}
			}

			foreach ($newUrls as $newUrl) {

				$html = FetchLogic::fetchRemoteData($newUrl);
				$dom = HtmlLogic::domObjectFromHtmlString($html);
				$metatags = HtmlLogic::readMetatagsFromHtmlString($html);

				$thumbnailUrl = str_replace("dynamic-rectangle-image", "dynamic-square-image", $metatags['og:image'] ?? "");

				$title = $metatags['og:title'] ?? "";
				$title = str_replace("| Lightbeans", "", $title);

				$tags = explode(' ', $title);
				$tags = array_filter($tags, fn($tag) => !in_array($tag, $config['bannedTags']));
				LogLogic::write("Resolved tags: " . implode(',', $tags));

				// Type
				$type = TYPE::PBR_MATERIAL;

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
					license: LICENSE::CUSTOM,
					creator: $this->creator,
					quirks: [
						QUIRK::SIGNUP_REQUIRED
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
		$image = FetchLogic::fetchRemoteData($url);
		$imagick = new Imagick();
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
