<?php

// twinbru

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
use log\LogLevel;
use log\Log;
use misc\StringUtil;

class CreatorLogicTwinbru extends CreatorLogic
{

	protected Creator $creator = Creator::TWINBRU;

	private string $tagRegex = '/[^A-Za-z0-9%]/';

	private string $indexingBaseUrl = 'https://textures.twinbru.com/ods/products';
	private array $indexingBaseParameters = [
		'pageSize' => 25,
		'sortAttribute' => 'launch',
		'sortDirection' => 'DSC',
		'prefilter' => 'status.eq.RN/bvs_special.ne.any(customer%20special,treatment%20special)/has3dTexture.eq.True'
	];
	private string $viewPageBaseUrl = 'https://textures.twinbru.com/products/';
	private string $viewPageSuffix = 'utm_source=3dassets.one';
	private string $thumbnailQueryBaseUrl = 'https://textures.twinbru.com/ods/assets';
	private string $thumbnailBaseUrl = 'https://textures.twinbru.com/assets/';
	private string $sessionCookieUrl = 'https://textures.twinbru.com/layout?item=products&account=bru';

	private function extractTags(array|string $input)
	{
		if (is_array($input)) {
			return array_merge(
				...array_map(
					fn($a) => preg_split($this->tagRegex, $a),
					array_values(
						$input
					)
				)
			);
		}
		return preg_split($this->tagRegex, $input);
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

		// Open a session

		$odsToken = new WebItemReference($this->sessionCookieUrl)->fetchCookie(
			targetCookieName: "ods-token"
		);


		// Collect assets

		$tmpCollection = new ScrapedAssetCollection();
		$page = $this->getCreatorState("page") ?? 1;

		$requestBody = $this->indexingBaseParameters;
		$requestBody["page"] = $page;



		$rawData = new WebItemReference(
			url: $this->indexingBaseUrl . "?" . http_build_query($requestBody),
			headers: ["Cookie" => "ods-token=$odsToken"]
		)->fetch()->parseAsJson();

		if ($rawData == null) {
			Log::write("Reset page counter because of an error.", null, LogLevel::WARNING);
			$page = 0;
		}

		if ($rawData) {
			$assetList = $rawData['results'];

			// Reset page counter
			if ($page >= $rawData['totalPageCount'] ?? 0) {
				Log::write("Reset page counter because end has been reached.");
				$page = 0;
			} else {
				Log::write("Page overview", ["current" => $page, "end" => ($rawData['totalPageCount'] ?? 0)], LogLevel::INFO);
			}

			foreach ($assetList as $twinbruAsset) {

				// Get the asset's fields
				$twinbruAsset = $twinbruAsset["item"];
				if (!$twinbruAsset) {
					continue;
				}

				// Build the asset's base URL
				$assetUrl = $this->viewPageBaseUrl . $twinbruAsset['itemId'] . "?" . $this->viewPageSuffix;

				// Create asset if it is not yet in DB
				if (!$existingAssets->containsUrl($assetUrl)) {

					// Thumbnail
					$thumbnailUrl = NULL;

					foreach (['BL_20', 'BL_20_CU'] as $viewType) {
						// Get the thumbnail URL
						$thumbnailQueryResponse = NULL;

						$thumbnailQueryResponse = new WebItemReference(
							headers: ["Cookie" => "ods-token=$odsToken"],
							url: $this->thumbnailQueryBaseUrl . "?" . http_build_query(["pageSize" => 200, "filter" => "renderView.eq.$viewType/stockId.eq." . $twinbruAsset['itemId']])
						)->fetch()->parseAsJson();

						if (sizeof($thumbnailQueryResponse['results']) > 0) {
							$thumbnailUrl = $this->thumbnailBaseUrl . $thumbnailQueryResponse['results'][0]['item']['assetId'] . "/Thumbnail.jpg";
							break;
						}
					}

					if (!$thumbnailUrl) {
						Log::write("Skipping because faulty thumbnail", LogLevel::ERROR);
						continue;
					}

					Log::write("Resolved thumbnail $thumbnailUrl");


					// Tags
					$tags = array_unique(
						array_filter(
							array_merge(
								$this->extractTags($twinbruAsset['class'] ?? ""),
								$this->extractTags($twinbruAsset['use'] ?? ""),
								$this->extractTags($twinbruAsset['finish'] ?? ""),
								$this->extractTags($twinbruAsset['quality'] ?? ""),
								$this->extractTags($twinbruAsset['characteristics'] ?? ""),
								$this->extractTags($twinbruAsset['brand'] ?? ""),
								$this->extractTags($twinbruAsset['company'] ?? ""),
								$this->extractTags($twinbruAsset['designName'] ?? ""),
								$this->extractTags($twinbruAsset['collectionName'] ?? ""),
								$this->extractTags($twinbruAsset['colouring'] ?? ""),
								$this->extractTags($twinbruAsset['main_colour_type_description'] ?? ""),
								$this->extractTags($twinbruAsset['cat_woven'] ?? []),
								$this->extractTags($twinbruAsset['end_use'] ?? []),
								$this->extractTags($twinbruAsset['colour_type_description'] ?? [])
							)
						)
					);

					Log::write("Resolved tags", $tags, LogLevel::INFO);

					// Type
					$type = ($twinbruAsset['has3dTexture'] ?? true) ? AssetType::PBR_MATERIAL : AssetType::OTHER;

					// Date
					$date = substr($twinbruAsset['launch'] ?? date("Ym"), 0, 4);
					$date .= "-";
					$date .= substr($twinbruAsset['launch'] ?? date("Ym"), 4, 2);
					$date .= "-01";

					// Name
					if ($twinbruAsset['designName'] == $twinbruAsset['collectionName']) {
						$name = $twinbruAsset['collectionName'] . " / " . $twinbruAsset['main_colour_type_description'];
					} else {
						$name = $twinbruAsset['designName'] . " / " . $twinbruAsset['collectionName'] . " / " . $twinbruAsset['main_colour_type_description'];
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
		}

		// Increase page counter
		$page += 1;
		$this->setCreatorState("page", $page);



		return $tmpCollection;
	}
}
