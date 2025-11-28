<?php

namespace creator\indexing;

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;
use misc\Fetch;
use creator\indexing\CreatorIndexer;
use misc\Log;
use Throwable;

// PBR PX

class CreatorIndexerPbrPx extends CreatorIndexer
{

	protected Creator $creator = Creator::PBR_PX;

	private string $indexingBaseUrl = "https://api.pbrpx.com/home/api_product/getPmsg";
	private string $assetBaseUrl = "https://api.pbrpx.com/home/api_product/getasset";
	private string $viewPageBaseUrl = "https://library.pbrpx.com/#/asset?asset=";
	private string $mediaBaseUrl = "https://asset.pbrpx.com/";
	private string $thumbnailIdentifierString = "preview_360_360";
	private int $maxAssetsPerRound = 5;



	public function validateAsset(Asset $asset): bool
	{

		// Extract the id from the url and compose the query
		$assetDetailsBody = ['asset' => end(explode("=", strtok($asset->url, '_')))];

		try {
			$response = Fetch::fetchRemoteJson(url: $this->assetBaseUrl, method: 'POST', body: json_encode($assetDetailsBody), jsonContentTypeHeader: true);

			// Test if there is an errno and if it is 0
			return isset($response['errno']) && $response['errno'] == 0;
		} catch (Throwable $e) {
			return false;
		}
	}

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$page = 1;

		$processedAssets = 0;
		$maxAssets = $this->maxAssetsPerRound;

		do {
			$assetsFoundThisIteration = 0;
			$assetListBody = ['page_number' => $page];
			$assetListRaw = Fetch::fetchRemoteJson(
				url: $this->indexingBaseUrl,
				method: 'POST',
				body: json_encode($assetListBody),
				jsonContentTypeHeader: true
			);

			$assetList = $assetListRaw['data']['data'];

			foreach ($assetList as $pbrPxAsset) {

				$assetsFoundThisIteration += 1;

				$assetUrl = $this->viewPageBaseUrl . $pbrPxAsset['id'];

				if (!in_array($assetUrl, $existingUrls)) {
					// Fetch asset details
					$assetDetailsBody = ['asset' => $pbrPxAsset['id']];
					$pbrPxAssetDetailsRaw = Fetch::fetchRemoteJson(
						url: $this->assetBaseUrl,
						method: 'POST',
						body: json_encode($assetDetailsBody),
						jsonContentTypeHeader: true
					);

					//Log::write("PBR PX Asset Details:");
					//Log::write(print_r($pbrPxAssetDetailsRaw));

					$pbrPxAssetDetails = $pbrPxAssetDetailsRaw['data'][0];

					// Extract information from response
					$tags = array_filter(preg_split('/[^A-Za-z0-9]/', $pbrPxAssetDetails['ename']));

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
					$thumbnailUrl = $this->mediaBaseUrl . $thumbnailUrlCandidates[0];
					foreach ($thumbnailUrlCandidates as $t) {
						if (str_contains($t, $this->thumbnailIdentifierString)) {
							$thumbnailUrl = $this->mediaBaseUrl . $t;
						}
					}

					Log::write("PBR PX Asset found: " . $pbrPxAssetDetails['ename'] . " | Type: " . $type->name() . " | URL: " . $assetUrl);

					// Build asset
					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $pbrPxAssetDetails['ename'],
						url: $assetUrl,
						thumbnailUrl: $thumbnailUrl,
						date: $pbrPxAsset['create_time'],
						tags: $tags,
						type: $type,
						license: License::CC0,
						creator: $this->creator,
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
