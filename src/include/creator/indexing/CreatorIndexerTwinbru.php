<?php

// twinbru

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
use log\LogLevel;
use log\Log;
use misc\StringUtil;

class CreatorIndexerTwinbru extends CreatorIndexer
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

	public function processUrl(string $url): string
	{
		return StringUtil::addHttpParameters($url, [
			'utm_source' => '3dassets.one',
			'utm_campaign' => 'twinbru'
		]);
	}

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		// Open a session

		$odsToken = Fetch::fetchRemoteCookie(
			targetCookieName: "ods-token",
			url: $this->sessionCookieUrl
		);


		// Collect assets

		$tmpCollection = new AssetCollection();
		$page = $this->getFetchingState("page") ?? 1;

		$requestBody = $this->indexingBaseParameters;
		$requestBody["page"] = $page;

		$headers = array_merge(Fetch::$defaultHeaders, ["Cookie" => "ods-token=$odsToken"]);

		$rawData = Fetch::fetchRemoteJson(
			headers: $headers,
			url: $this->indexingBaseUrl . "?" . http_build_query($requestBody),
			method: 'GET'
		);

		if ($rawData == "") {
			Log::write("Reset page counter because of an error.");
			$page = 0;
		}

		if ($rawData) {
			$assetList = $rawData['results'];

			// Reset page counter
			if ($page >= $rawData['totalPageCount'] ?? 0) {
				Log::write("Reset page counter because end has been reached.");
				$page = 0;
			} else {
				Log::write("Current page is $page, end page is " . ($rawData['totalPageCount'] ?? 0));
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
				if (!in_array($assetUrl, $existingUrls)) {

					// Thumbnail
					$thumbnailUrl = NULL;

					foreach (['BL_20', 'BL_20_CU'] as $viewType) {
						// Get the thumbnail URL
						$thumbnailQueryResponse = NULL;
						$thumbnailQueryResponse = Fetch::fetchRemoteJson(
							headers: $headers,
							url: $this->thumbnailQueryBaseUrl . "?" . http_build_query(["pageSize" => 200, "filter" => "renderView.eq.$viewType/stockId.eq." . $twinbruAsset['itemId']])
						);

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

					Log::write("Resolved tags: " . implode(',', $tags));

					// Type
					$type = ($twinbruAsset['has3dTexture'] ?? true) ? Type::PBR_MATERIAL : Type::OTHER;

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
					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $name,
						url: $assetUrl,
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
		}

		// Increase page counter
		Log::write("Increasing page counter.");
		$page += 1;
		$this->saveFetchingState("page", $page);



		return $tmpCollection;
	}
}
