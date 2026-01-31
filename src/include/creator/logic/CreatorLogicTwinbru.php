<?php

// twinbru

namespace creator\logic;


use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use DateTime;
use fetch\WebItemReference;
use log\LogLevel;
use log\Log;
use misc\StringUtil;

class CreatorLogicTwinbru extends CreatorLogic
{

	protected Creator $creator = Creator::TWINBRU;
	private string $tagRegex = '/[^A-Za-z0-9%]/';

	// Configuration for indexing products
	private string $indexingBaseUrl = "https://textures.twinbru.com/api/ods/products";

	/**
	 * 
	 * @var array<string,mixed>
	 */
	private array $indexingBaseParameters = [
		'pageSize' => 50,
		'sortAttribute' => 'launch',
		'sortDirection' => 'DSC',
		'prefilter' => 'status.eq.RN/bvs_special.ne.any(customer%20special,treatment%20special)/has3dTexture.eq.True'
	];

	// Configuration for viewing
	private string $viewPageBaseUrl = 'https://textures.twinbru.com/products/';

	// Configuration for thumbnails
	private string $thumbnailQueryBaseUrl = 'https://textures.twinbru.com/api/ods/assets';
	private string $thumbnailBaseUrl = 'https://cdn.twinbru.com/ods/assets/';


	/**
	 * 
	 * @param array<string>|string $input 
	 * @return array<string>
	 */
	private function extractTags(array|string $input): array
	{
		if (is_array($input)) {
			return array_merge(
				...array_map(
					fn($a) => preg_split($this->tagRegex, $a) ?: [],
					array_values(
						$input
					)
				)
			);
		} else {
			$result = preg_split($this->tagRegex, $input);
			if ($result === false) {
				return [];
			} else {
				return $result;
			}
		}
	}

	public function postprocessUrl(string $url): string
	{
		return StringUtil::addHttpParameters($url, [
			'utm_source' => '3dassets.one',
			'utm_campaign' => 'twinbru'
		]);
	}

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		// Prepare the collection
		$tmpCollection = new ScrapedAssetCollection();

		// Determine current API page to scrape
		$page = intval($this->getCreatorState("page") ?? 1);

		// Fetch the raw list from the API
		$requestBody = $this->indexingBaseParameters;
		$requestBody["page"] = $page;
		$rawOdsProductData = new WebItemReference(
			url: $this->indexingBaseUrl . "?" . http_build_query($requestBody),
		)->fetch()->parseAsJson();

		if ($rawOdsProductData) {
			$odsProductList = $rawOdsProductData['results'] ?? [];

			foreach ($odsProductList as $odsProduct) {

				// Get the asset's fields
				$odsProduct = $odsProduct["item"];
				if (!$odsProduct) {
					continue;
				}

				// Build the asset's base URL
				$assetUrl = $this->viewPageBaseUrl . $odsProduct['itemId'];

				// Create asset if it is not yet in DB
				if (!$existingAssets->containsUrl($assetUrl)) {

					// Thumbnail
					$thumbnailUrl = NULL;

					// Query for thumbnail data, trying different render scenes
					foreach (['Swatch_ruler', 'BL_20_CU', 'BL_65_CU', 'BL_20', 'BL_65'] as $renderScene) {

						// Build a query for this render scene
						$thumbnailQueryResponse = new WebItemReference(
							url: $this->thumbnailQueryBaseUrl . "?" . http_build_query(["pageSize" => 200, "filter" => "renderScene.eq.$renderScene/stockId.eq." . $odsProduct['itemId']])
						)->fetch()->parseAsJson();

						// If we found a result, build the thumbnail URL and stop trying
						if (
							$thumbnailQueryResponse !== null
							&& key_exists('results', $thumbnailQueryResponse)
							&& sizeof($thumbnailQueryResponse['results']) > 0
						) {
							$thumbnailUrl = $this->thumbnailBaseUrl . $thumbnailQueryResponse['results'][0]['item']['assetId'] . "/Thumbnail.jpg";
							break;
						} else {
							Log::write("Failed to fetch thumbnail data for render scene $renderScene", null, LogLevel::WARNING);
						}
					}

					if (!$thumbnailUrl) {
						Log::write("Skipping asset because no thumbnail could be resolved", $assetUrl, LogLevel::ERROR);
						continue;
					}

					Log::write("Resolved thumbnail ", $thumbnailUrl, LogLevel::DEBUG);


					// Tags
					$tags = array_unique(
						array_filter(
							array_merge(
								$this->extractTags($odsProduct['class'] ?? ""),
								$this->extractTags($odsProduct['use'] ?? ""),
								$this->extractTags($odsProduct['finish'] ?? ""),
								$this->extractTags($odsProduct['quality'] ?? ""),
								$this->extractTags($odsProduct['characteristics'] ?? ""),
								$this->extractTags($odsProduct['brand'] ?? ""),
								$this->extractTags($odsProduct['company'] ?? ""),
								$this->extractTags($odsProduct['designName'] ?? ""),
								$this->extractTags($odsProduct['collectionName'] ?? ""),
								$this->extractTags($odsProduct['colouring'] ?? ""),
								$this->extractTags($odsProduct['main_colour_type_description'] ?? ""),
								$this->extractTags($odsProduct['cat_woven'] ?? []),
								$this->extractTags($odsProduct['end_use'] ?? []),
								$this->extractTags($odsProduct['colour_type_description'] ?? [])
							)
						)
					);

					Log::write("Resolved tags", $tags, LogLevel::DEBUG);

					// Type
					$type = ($odsProduct['has3dTexture'] ?? true) ? AssetType::PBR_MATERIAL : AssetType::OTHER;

					// Date
					$date = substr($odsProduct['launch'] ?? date("Ym"), 0, 4);
					$date .= "-";
					$date .= substr($odsProduct['launch'] ?? date("Ym"), 4, 2);
					$date .= "-01";

					// Name
					if ($odsProduct['designName'] == $odsProduct['collectionName']) {
						$name = $odsProduct['collectionName'] . " / " . $odsProduct['main_colour_type_description'];
					} else {
						$name = $odsProduct['designName'] . " / " . $odsProduct['collectionName'] . " / " . $odsProduct['main_colour_type_description'];
					}

					// Build asset
					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $name,
						url: $assetUrl,
						date: new DateTime($date),
						tags: $tags,
						type: $type,
						creator: $this->creator,
						status: ScrapedAssetStatus::NEWLY_FOUND,
						rawThumbnailData: new WebItemReference(
							url: $thumbnailUrl
						)->fetch()->content
					);
				}
			}

			if (sizeof($odsProductList) > 0) {
				// Increment page for next scrape run
				$this->setCreatorState("page", $page + 1);
			}
		}

		if ($rawOdsProductData == null || sizeof($odsProductList) == 0) {
			Log::write("Reset page counter because of an error.", null, LogLevel::WARNING);
			$this->setCreatorState("page", 1);
		}

		return $tmpCollection;
	}
}
