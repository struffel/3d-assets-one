<?php

namespace creator\indexing;
// CGMood

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use asset\Quirk;
use Exception;
use Fetch;
use creator\indexing\CreatorIndexer;
use misc\Html;
use misc\Log;
use Rct567\DomQuery\DomQuery;

class CreatorIndexerCgMood extends CreatorIndexer
{

	protected Creator $creator = Creator::CGMOOD;

	private string $indexingBaseUrl = "https://cgmood.com/free?page=";
	private int $maxPagesPerIteration = 3;
	private array $urlTypeRegex = [
		"#/3d-model/#" => Type::MODEL_3D,
		"#/material/#" => Type::PBR_MATERIAL
	];

	public function validateAsset(Asset $asset): bool
	{
		$rawHtml = Fetch::fetchRemoteData($asset->url);

		$dom = Html::domObjectFromHtmlString($rawHtml);
		$domQuery = new DomQuery($dom);

		$downloadButtonCandidates = $domQuery->find('.download-button');

		if (sizeof($downloadButtonCandidates) > 0) {
			$downloadButton = $downloadButtonCandidates[0];
			return preg_match('/.*Free download.*/', $downloadButton->text());
		} else {
			return false;
		}
	}

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();

		$page = $this->getFetchingState("page") ?? 1;
		$pagesProcessed = 0;

		do {
			$attempts = 0;
			$rawHtml = "";
			while (!$rawHtml) {
				try {
					$rawHtml = Fetch::fetchRemoteData($this->indexingBaseUrl . $page);
				} catch (\Throwable $th) {
					Log::write("Failed to load site. Attempt: $attempts", "WARN");
					sleep($attempts * 2);
					$attempts = $attempts + 1;

					if ($attempts > 4) {
						throw new Exception("Failed to load site, even after multiple attempts.");
					}
				}
			}

			$dom = Html::domObjectFromHtmlString($rawHtml);
			$domQuery = new DomQuery($dom);

			$assetImageElements = $domQuery->find('.product img');
			$assetsFoundThisIteration = sizeof($assetImageElements);

			foreach ($assetImageElements as $assetImageElement) {

				$type = NULL;

				foreach ($this->urlTypeRegex as $regex => $currentType) {
					if (preg_match($regex, $assetImageElement->attr('data-product-url'))) {
						$type = $currentType;
					}
				}
				if (!$type) {
					Log::write("Skipping " . $assetImageElement->attr('data-product-url') . " because it does not match the URL schema.");
				} elseif (!in_array($assetImageElement->attr('data-product-url'), $existingUrls)) {

					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $assetImageElement->attr('data-product-title'),
						url: $assetImageElement->attr('data-product-url'),
						thumbnailUrl: "https://cgmood.com" . $assetImageElement->attr('src'),
						date: date("Y-m-d"),
						tags: array_filter(preg_split('/[^A-Za-z0-9]/', $assetImageElement->attr('data-product-title'))),
						type: $type,
						license: License::CUSTOM,
						creator: Creator::CGMOOD,
						quirks: [Quirk::SIGNUP_REQUIRED, Quirk::LIMITED_FREE_DOWNLOADS],
						status: AssetStatus::PENDING
					);
				}
			}

			$page += 1;
			$pagesProcessed += 1;

			if ($assetsFoundThisIteration < 1) {
				$page = 1;
			}
		} while ($pagesProcessed < $this->maxPagesPerIteration);

		$this->saveFetchingState("page", $page);

		return $tmpCollection;
	}
}
