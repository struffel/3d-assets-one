<?php

namespace creator\logic;
// CGMood

use asset\Asset;
use asset\ScrapedAsset;
use asset\ScrapedAssetStatus;
use asset\CommonLicense;
use asset\AssetType;
use asset\ScrapedAssetCollection;
use asset\StoredAssetCollection;
use creator\Creator;
use Exception;
use creator\CreatorLogic;
use DateTime;
use fetch\WebItemReference;
use log\LogLevel;
use log\Log;
use Rct567\DomQuery\DomQuery;

class CreatorLogicCgMood extends CreatorLogic
{

	protected Creator $creator = Creator::CGMOOD;

	protected int $maxAssetsPerRun = 3;

	private string $indexingBaseUrl = "https://cgmood.com/free?page=";
	/** @var array<string, AssetType> */
	private array $urlTypeRegex = [
		"#/3d-model/#" => AssetType::MODEL_3D,
		"#/material/#" => AssetType::PBR_MATERIAL
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
			$buttonText = (string) $downloadButton->text();
			return preg_match('/.*Free download.*/', $buttonText) === 1;
		} else {
			return false;
		}
	}

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();

		$page = $this->getCreatorState("page") ?? 1;
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
				} elseif (!$existingAssets->containsUrl($assetImageElement->attr('data-product-url'))) {

					$titleParts = preg_split('/[^A-Za-z0-9]/', $assetImageElement->attr('data-product-title'));
					$tags = $titleParts !== false ? array_filter($titleParts) : [];

					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $assetImageElement->attr('data-product-title'),
						url: $assetImageElement->attr('data-product-url'),
						tags: array_values($tags),
						type: $type,

						creator: Creator::CGMOOD,
						status: ScrapedAssetStatus::NEWLY_FOUND,
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
		} while ($pagesProcessed < $this->maxAssetsPerRun);

		$this->setCreatorState("page", $page);

		return $tmpCollection;
	}
}
