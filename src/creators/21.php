<?php

// twinbru

class CreatorFetcher21 extends CreatorFetcher
{

	public CREATOR $creator = CREATOR::TWINBRU;

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

		try {
			$rawData = FetchLogic::fetchRemoteJson(
				headers: $headers,
				url: $config['indexingBaseUrl'] . "?" . http_build_query($requestBody),
				method: 'GET'
			);
		} catch (Throwable $e) {
			$page = 0;
			$rawData = NULL;
			LogLogic::write($e->getMessage(), "ERROR");
		}

		if ($rawData) {
			$assetList = $rawData['results'];

			foreach ($assetList as $twinbruAsset) {

				// Get the asset's fields
				$twinbruAsset = $twinbruAsset["item"];
				if (!$twinbruAsset) {
					continue;
				}

				// Build the asset's base URL
				$assetUrl = $config['viewPageBaseUrl'] . $twinbruAsset['itemId'];

				// Create asset if it's not recognized
				if (!in_array($assetUrl, $existingUrls)) {

					// Get the thumbnail URL
					$thumbnailQueryResponse = NULL;
					$thumbnailQueryResponse = FetchLogic::fetchRemoteJson(
						headers: $headers,
						url: $config['thumbnailQueryBaseUrl'] . "?" . http_build_query(["filter" => "stockId.eq." . $twinbruAsset['itemId']])
					);

					$thumbnailUrl = NULL;

					// try for BL_20

					foreach ($thumbnailQueryResponse['results'] as $result) {

						if ((($result['item'] ?? [])['renderScene'] ?? "") != "BL_20") {
							continue;
						}

						$thumbnailUrl = $config['thumbnailBaseUrl'] . $result['item']['assetId'] . "/Thumbnail.jpg";
					}

					// try other BL_* as alternative
					if (!$thumbnailUrl) {
						foreach ($thumbnailQueryResponse['results'] as $result) {

							if (!preg_match('/BL_[0-9]+/', (($result['item'] ?? [])['renderScene'] ?? ""))) {
								continue;
							}

							$thumbnailUrl = $config['thumbnailBaseUrl'] . $result['item']['assetId'] . "/Thumbnail.jpg";
						}
					}



					LogLogic::write("Resolved thumbnail $thumbnailUrl");

					if (!$thumbnailUrl) {
						LogLogic::write("Skipping because faulty thumbnail", "WARN");
						continue;
					}



					// Extract information from response
					$tags = ["fabric"];
					array_merge($tags, preg_split('/[^A-Za-z0-9%]/', $twinbruAsset['quality'] ?? ""));
					array_merge($tags, preg_split('/[^A-Za-z0-9%]/', $twinbruAsset['characteristics'] ?? ""));
					array_merge($tags, preg_split('/[^A-Za-z0-9%]/', $twinbruAsset['brand'] ?? ""));
					array_merge($tags, preg_split('/[^A-Za-z0-9%]/', $twinbruAsset['designName'] ?? ""));
					array_merge($tags, preg_split('/[^A-Za-z0-9%]/', $twinbruAsset['collectionName'] ?? ""));
					array_merge($tags, preg_split('/[^A-Za-z0-9%]/', $twinbruAsset['main_colour_type_description'] ?? ""));

					$tags = array_unique(array_filter($tags));

					$type = TYPE::PBR_MATERIAL;

					$date = substr($twinbruAsset['launch'] ?? date("Ym"), 0, 4);
					$date .= "-";
					$date .= substr($twinbruAsset['launch'] ?? date("Ym"), 4, 2);
					$date .= "-01";

					// Build asset
					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $twinbruAsset['designName'] . " / " . $twinbruAsset['collectionName'] . " / " . $twinbruAsset['main_colour_type_description'],
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
						status: ASSET_STATUS::PENDING
					);
				}
			}
		}

		// Increase page counter
		LogLogic::write("Increasing page counter.");
		$page += 1;
		$this->saveFetchingState("page", $page);



		return $tmpCollection;
	}
}
