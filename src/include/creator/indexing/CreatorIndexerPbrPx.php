<?php

namespace creator\indexing;

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use Fetch;
use indexing\CreatorIndexer;
use Throwable;

// PBR PX

class CreatorIndexerPbrPx extends CreatorIndexer
{

	protected static Creator $creator = Creator::PBR_PX;

	private static string $indexingBaseUrl = "https://api.pbrpx.com/home/api_product/getPmsg";
	private static string $assetBaseUrl = "https://api.pbrpx.com/home/api_product/getasset";
	private static string $viewPageBaseUrl = "https://library.pbrpx.com/#/asset?asset=";
	private static string $mediaBaseUrl = "https://asset.pbrpx.com/";
	private static string $thumbnailIdentifierString = "preview_360_360";
	private static int $maxAssetsPerRound = 5;



	public static function validateAsset(Asset $asset): bool
	{

		// Extract the id from the url and compose the query
		$assetDetailsBody = ['asset' => end(explode("=", strtok($asset->url, '_')))];

		try {
			$response = Fetch::fetchRemoteJson(url: self::$assetBaseUrl, method: 'POST', body: json_encode($assetDetailsBody), jsonContentTypeHeader: true);

			// Test if there is an errno and if it is 0
			return isset($response['errno']) && $response['errno'] == 0;
		} catch (Throwable $e) {
			return false;
		}
	}

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$page = 1;

		$processedAssets = 0;
		$maxAssets = self::$maxAssetsPerRound;

		do {
			$assetsFoundThisIteration = 0;
			$assetListBody = ['page_number' => $page];
			$assetListRaw = Fetch::fetchRemoteJson(url: self::$indexingBaseUrl, method: 'POST', body: json_encode($assetListBody), jsonContentTypeHeader: true);

			$assetList = $assetListRaw['data']['data'];

			foreach ($assetList as $pbrPxAsset) {

				$assetsFoundThisIteration += 1;

				$assetUrl = self::$viewPageBaseUrl . $pbrPxAsset['id'];

				if (!in_array($assetUrl, $existingUrls)) {
					// Fetch asset details
					$assetDetailsBody = ['asset' => $pbrPxAsset['id']];
					$pbrPxAssetDetailsRaw = Fetch::fetchRemoteJson(url: self::$assetBaseUrl, method: 'POST', body: json_encode($assetDetailsBody), jsonContentTypeHeader: true);

					//Log::write(print_r($pbrPxAssetDetailsRaw));

					$pbrPxAssetDetails = $pbrPxAssetDetailsRaw['data'][0];

					// Extract information from response
					$tags = array_filter(preg_split('/[^A-Za-z0-9]/', $pbrPxAssetDetails['tag']));

					$type = Type::OTHER;
					if (str_starts_with($pbrPxAssetDetails['zips'], "HDRI")) {
						$type = Type::HDRI;
					} elseif (str_starts_with($pbrPxAssetDetails['zips'], "Textures")) {
						$type = Type::PBR_MATERIAL;
					} elseif (str_starts_with($pbrPxAssetDetails['zips'], "3D_Model")) {
						$type = Type::MODEL_3D;
					}

					// Decide on thumbnail
					$thumbnailUrlCandidates = explode("+", $pbrPxAssetDetails['img_url']);
					$thumbnailUrl = self::$mediaBaseUrl . $thumbnailUrlCandidates[0];
					foreach ($thumbnailUrlCandidates as $t) {
						if (str_contains($t, self::$thumbnailIdentifierString)) {
							$thumbnailUrl = self::$mediaBaseUrl . $t;
						}
					}

					// Build asset
					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $pbrPxAsset['ename'],
						url: $assetUrl,
						thumbnailUrl: $thumbnailUrl,
						date: $pbrPxAsset['create_time'],
						tags: $tags,
						type: $type,
						license: License::CC0,
						creator: self::$creator,
						quirks: [],
						status: AssetStatus::PENDING
					);

					$processedAssets += 1;
					if ($processedAssets > $maxAssets) {
						break;
					}
				}
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0 && $processedAssets < $maxAssets);

		return $tmpCollection;
	}
}
