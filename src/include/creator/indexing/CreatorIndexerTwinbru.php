<?php

// twinbru

namespace creator\indexing;

use asset\AssetStatus;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Fetch;
use indexing\CreatorIndexer;
use misc\Log;
use misc\Strings;

class CreatorIndexerTwinbru extends CreatorIndexer
{

	protected static Creator $creator = Creator::TWINBRU;

	private static string $tagRegex = '/[^A-Za-z0-9%]/';

	private static string $indexingBaseUrl = 'https://textures.twinbru.com/ods/products';
	private static array $indexingBaseParameters = [
		'pageSize' => 25,
		'sortAttribute' => 'launch',
		'sortDirection' => 'DSC',
		'prefilter' => 'status.eq.RN/bvs_special.ne.any(customer%20special,treatment%20special)/has3dTexture.eq.True'
	];
	private static string $viewPageBaseUrl = 'https://textures.twinbru.com/products/';
	private static string $viewPageSuffix = 'utm_source=3dassets.one';
	private static string $thumbnailQueryBaseUrl = 'https://textures.twinbru.com/ods/assets';
	private static string $thumbnailBaseUrl = 'https://textures.twinbru.com/assets/';
	private static string $sessionCookieUrl = 'https://textures.twinbru.com/layout?item=products&account=bru';

	private static function extractTags(array|string $input)
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

	public static function processUrl(string $url): string
	{
		return Strings::addHttpParameters($url, [
			'utm_source' => '3dassets.one',
			'utm_campaign' => 'twinbru'
		]);
	}

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		// Open a session

		$odsToken = Fetch::fetchRemoteCookie(
			targetCookieName: "ods-token",
			url: self::$sessionCookieUrl
		);


		// Collect assets

		$tmpCollection = new AssetCollection();
		$page = self::getFetchingState("page") ?? 1;

		$requestBody = self::$indexingBaseParameters;
		$requestBody["page"] = $page;

		$headers = array_merge(Fetch::$defaultHeaders, ["Cookie" => "ods-token=$odsToken"]);

		$rawData = Fetch::fetchRemoteJson(
			headers: $headers,
			url: self::$indexingBaseUrl . "?" . http_build_query($requestBody),
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
				$assetUrl = self::$viewPageBaseUrl . $twinbruAsset['itemId'] . "?" . self::$viewPageSuffix;

				// Create asset if it is not yet in DB
				if (!in_array($assetUrl, $existingUrls)) {

					// Thumbnail
					$thumbnailUrl = NULL;

					foreach (['BL_20', 'BL_20_CU'] as $viewType) {
						// Get the thumbnail URL
						$thumbnailQueryResponse = NULL;
						$thumbnailQueryResponse = Fetch::fetchRemoteJson(
							headers: $headers,
							url: self::$thumbnailQueryBaseUrl . "?" . http_build_query(["pageSize" => 200, "filter" => "renderView.eq.$viewType/stockId.eq." . $twinbruAsset['itemId']])
						);

						if (sizeof($thumbnailQueryResponse['results']) > 0) {
							$thumbnailUrl = self::$thumbnailBaseUrl . $thumbnailQueryResponse['results'][0]['item']['assetId'] . "/Thumbnail.jpg";
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
								self::extractTags($twinbruAsset['class'] ?? ""),
								self::extractTags($twinbruAsset['use'] ?? ""),
								self::extractTags($twinbruAsset['finish'] ?? ""),
								self::extractTags($twinbruAsset['quality'] ?? ""),
								self::extractTags($twinbruAsset['characteristics'] ?? ""),
								self::extractTags($twinbruAsset['brand'] ?? ""),
								self::extractTags($twinbruAsset['company'] ?? ""),
								self::extractTags($twinbruAsset['designName'] ?? ""),
								self::extractTags($twinbruAsset['collectionName'] ?? ""),
								self::extractTags($twinbruAsset['colouring'] ?? ""),
								self::extractTags($twinbruAsset['main_colour_type_description'] ?? ""),
								self::extractTags($twinbruAsset['cat_woven'] ?? []),
								self::extractTags($twinbruAsset['end_use'] ?? []),
								self::extractTags($twinbruAsset['colour_type_description'] ?? [])
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
					$tmpCollection->assets[] = new Type(
						id: NULL,
						name: $name,
						url: $assetUrl,
						thumbnailUrl: $thumbnailUrl,
						date: $date,
						tags: $tags,
						type: $type,
						license: License::CUSTOM,
						creator: self::$creator,
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
		self::saveFetchingState("page", $page);



		return $tmpCollection;
	}
}
