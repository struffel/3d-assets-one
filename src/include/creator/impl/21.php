<?php

// twinbru

class CreatorFetcher21 extends CreatorFetcher
{

	public CREATOR $creator = CREATOR::TWINBRU;

	private static string $tagRegex = '/[^A-Za-z0-9%]/';

	private function extractTags(array|string $input)
	{
		if (is_array($input)) {
			return array_merge(
				...array_map(
					fn($a) => preg_split(self::$tagRegex, $a),
					array_values(
						$input
					)
				)
			);
		}
		return preg_split(self::$tagRegex, $input);
	}

	public function processUrl(string $url): string
	{
		return StringLogic::addHttpParameters($url, [
			'utm_source' => '3dassets.one',
			'utm_campaign' => 'twinbru'
		]);
	}

	function findNewAssets(array $existingUrls, array $config): AssetCollection
	{

		// Open a session

		$odsToken = FetchLogic::fetchRemoteCookie(
			targetCookieName: "ods-token",
			url: $config['sessionCookieUrl']
		);


		// Collect assets

		$tmpCollection = new AssetCollection();
		$page = $this->getFetchingState("page") ?? 1;

		$requestBody = $config['indexingBaseParameters'];
		$requestBody["page"] = $page;

		$headers = array_merge(FetchLogic::$defaultHeaders, ["Cookie" => "ods-token=$odsToken"]);

		$rawData = FetchLogic::fetchRemoteJson(
			headers: $headers,
			url: $config['indexingBaseUrl'] . "?" . http_build_query($requestBody),
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
				$assetUrl = $config['viewPageBaseUrl'] . $twinbruAsset['itemId'] . "?" . $config['viewPageSuffix'];

				// Create asset if it is not yet in DB
				if (!in_array($assetUrl, $existingUrls)) {

					// Thumbnail
					$thumbnailUrl = NULL;

					foreach (['BL_20', 'BL_20_CU'] as $viewType) {
						// Get the thumbnail URL
						$thumbnailQueryResponse = NULL;
						$thumbnailQueryResponse = FetchLogic::fetchRemoteJson(
							headers: $headers,
							url: $config['thumbnailQueryBaseUrl'] . "?" . http_build_query(["pageSize" => 200, "filter" => "renderView.eq.$viewType/stockId.eq." . $twinbruAsset['itemId']])
						);

						if (sizeof($thumbnailQueryResponse['results']) > 0) {
							$thumbnailUrl = $config['thumbnailBaseUrl'] . $thumbnailQueryResponse['results'][0]['item']['assetId'] . "/Thumbnail.jpg";
							break;
						}
					}

					if (!$thumbnailUrl) {
						Log::write("Skipping because faulty thumbnail", "WARN");
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
					$type = ($twinbruAsset['has3dTexture'] ?? true) ? TYPE::PBR_MATERIAL : TYPE::OTHER;

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
						license: LICENSE::CUSTOM,
						creator: $this->creator,
						quirks: [
							QUIRK::SIGNUP_REQUIRED
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
