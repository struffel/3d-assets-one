<?php

namespace creator\indexing;
// CGMood

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use Exception;

use creator\indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
use log\LogLevel;
use misc\Html;
use log\Log;
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

		$response = (new WebItemReference($asset->url))->fetch();
		if ($response->httpStatusCode != 200) {
			return false;
		}
		$dom = $response->parseAsDomDocument();
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

			$dom = (new WebItemReference($this->indexingBaseUrl . $page))->fetch()->parseAsDomDocument();
			$domQuery = new DomQuery($dom);

			$assetImageElements = $domQuery->find('.product img');

			foreach ($assetImageElements as $assetImageElement) {

				$type = NULL;

				foreach ($this->urlTypeRegex as $regex => $currentType) {
					if (preg_match($regex, $assetImageElement->attr('data-product-url'))) {
						$type = $currentType;
					}
				}
				if (!$type) {
					Log::write("Skipping URL because it does not match the URL schema.", $assetImageElement->attr('data-product-url'), LogLevel::WARNING);
				} elseif (!in_array($assetImageElement->attr('data-product-url'), $existingUrls)) {

					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $assetImageElement->attr('data-product-title'),
						url: $assetImageElement->attr('data-product-url'),
						thumbnailUrl: "https://cgmood.com" . $assetImageElement->attr('src'),
						date: new DateTime(),
						tags: array_filter(preg_split('/[^A-Za-z0-9]/', $assetImageElement->attr('data-product-title'))),
						type: $type,
						license: License::CUSTOM,
						creator: Creator::CGMOOD,
						status: AssetStatus::ACTIVE,
						rawThumbnailData: new WebItemReference(
							url: "https://cgmood.com" . $assetImageElement->attr('src')
						)->fetch()->content
					);
				}
			}

			$page += 1;
			$pagesProcessed += 1;

			if (sizeof($assetImageElements) < 1) {
				$page = 1;
			}
		} while ($pagesProcessed < $this->maxPagesPerIteration);

		$this->saveFetchingState("page", $page);

		return $tmpCollection;
	}
}
