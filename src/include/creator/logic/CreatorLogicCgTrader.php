<?php

namespace creator\logic;

use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use DateTime;
use Exception;
use fetch\WebItemReference;
use log\Log;
use log\LogLevel;
use Rct567\DomQuery\DomQuery;

class CreatorLogicCgTrader extends CreatorLogic
{

	protected Creator $creator = Creator::CGTRADER;

	private string $indexingBaseUrl = "https://www.cgtrader.com/3d-models?free=1&licenses%5B%5D=cgt_standard_only&licenses%5B%5D=exclude_3d_print&licenses%5B%5D=exclude_editorial&licenses%5B%5D=exclude_adult_content&per_page=10&page=";
	protected int $maxAssetsPerRun = 20;

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();
		$page = $this->getCreatorState("page") ?? 1;

		do {
			$response = (new WebItemReference($this->indexingBaseUrl . $page))->fetch();

			if ($response->httpStatusCode != 200) {
				// We have reached the end (or an error)
				// Start from the beginning next time
				$page = 1;
				$response = (new WebItemReference($this->indexingBaseUrl . $page))->fetch();
			}

			$domQuery = $response->parseAsDomQuery();

			if ($domQuery === null) {
				throw new Exception("Error while trying to build DOM query.");
			}

			// Find all product cards on the page
			$modelCards = $domQuery->find('.card-3d-model');
			$cardsFoundThisIteration = sizeof($modelCards);

			foreach ($modelCards as $modelCard) {

				if (sizeof($tmpCollection) >= $this->maxAssetsPerRun) {
					break 2; // Break out of both loops
				}

				// Find the URL of the asset
				$assetLink = $modelCard->find('a.cgt-model-card__link');
				$url = $assetLink->attr('href');

				if (!$url) {
					Log::write("Skipping model card with unresolvable URL", $this->indexingBaseUrl . $page, LogLevel::WARNING);
					continue;
				}

				// Check if already exists
				if (!$existingAssets->containsUrl($url) && !$tmpCollection->containsUrl($url)) {

					// Get the full product page
					$modelPage = new WebItemReference($url)->fetch()->parseAsDomQuery();

					// Skip works which are marked as AI-generated.
					// This won't be 100% effective because it relies on proper moderation from CGTrader but I didn't find a better method at this point.
					$aiGeneratedTag = $modelPage->find('.pricing-area-wrapper *[data-for="tooltip-ai-generated"]');
					if (sizeof($aiGeneratedTag) > 0) {
						Log::write("Skipped AI generated model", $url, LogLevel::INFO);
						continue;
					}

					// Get the name
					$titleElement = $modelPage->find('.pricing-area__title');
					$name = trim($titleElement->text());

					if (empty($name)) {
						Log::write("Skipping asset with empty name", $url, LogLevel::WARNING);
						continue;
					}

					// Get thumbnail
					$thumbnailImg = $modelPage->find('.gallery-area')->find('img');
					$thumbnailUrl = $thumbnailImg->attr('src');

					if (!$thumbnailUrl) {
						Log::write("Skipping asset with unresolvable thumbnail", $url, LogLevel::WARNING);
						continue;
					}

					// Extract tags from the name and the website
					$tags = preg_split('/[\s\-_]+/', $name) ?: [];

					$tagLinks = $modelPage->find('.description-area__related-tags');
					foreach ($tagLinks as $tagLink) {
						$tags[] = $tagLink->text();
					}

					$tags = array_filter($tags, fn($tag) => strlen($tag) > 1);
					$tags = array_unique($tags);

					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $name,
						url: $url,
						date: new DateTime(),
						tags: array_values($tags),
						type: AssetType::MODEL_3D,
						creator: Creator::CGTRADER,
						status: ScrapedAssetStatus::NEWLY_FOUND,
						rawThumbnailData: (new WebItemReference(url: $thumbnailUrl))->fetch()->content
					);
				}
			}

			$page += 1;
		} while ($cardsFoundThisIteration > 0 && $page < 1000 /* Failsafe */);

		$this->setCreatorState("page", $page);

		return $tmpCollection;
	}
}
