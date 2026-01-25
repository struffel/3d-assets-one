<?php

namespace creator\logic;

use asset\Asset;
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
use Throwable;

// PBR PX

class CreatorLogicPbrPx extends CreatorLogic
{

	protected Creator $creator = Creator::PBR_PX;

	private string $indexingApiBaseUrl = "https://api.pbrpx.com/home/api_product/getPmsg?page_number=";
	private string $assetApiBaseUrl = "https://api.pbrpx.com/home/api_product/getasset";
	private string $assetViewingBaseUrl = "https://library.pbrpx.com/#/asset?asset=";
	private string $mediaBaseUrl = "https://asset.pbrpx.com/";
	private string $thumbnailIdentifierString = "preview_360_360";
	protected int $maxAssetsPerRun = 25;

	public function validateAsset(Asset $asset): bool
	{

		// Extract the id from the url and compose the query
		$assetDetailsBody = ['asset' => end(explode("=", strtok($asset->url, '_')))];

		try {
			$response = new WebItemReference(
				url: $this->assetApiBaseUrl,
				method: 'POST',
				requestBody: json_encode($assetDetailsBody),
				headers: [
					'Content-Type' => 'application/json'
				]
			);

			// Test if there is an errno and if it is 0
			return isset($response['errno']) && $response['errno'] == 0;
		} catch (Throwable $e) {
			return false;
		}
	}

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();
		$page = 1;

		$processedAssets = 0;
		$maxAssets = $this->maxAssetsPerRun;

		do {
			$assetsFoundThisIteration = 0;
			$assetListRaw = new WebItemReference(
				url: $this->indexingApiBaseUrl . $page
			)->fetch()->parseAsJson();

			$assetList = $assetListRaw['data']['data'];

			foreach ($assetList as $pbrPxAsset) {

				$assetsFoundThisIteration += 1;

				$assetUrl = $this->assetViewingBaseUrl . $pbrPxAsset['id'];

				if (!$existingAssets->containsUrl($assetUrl)) {

					// Fetch asset details
					$pbrPxAssetDetailsRaw = new WebItemReference(
						url: $this->assetApiBaseUrl,
						method: 'POST',
						requestBody: json_encode([
							'asset' => strval($pbrPxAsset['id'])
						]),
						headers: [
							'Content-Type' => 'application/json'
						]
					)->fetch()->parseAsJson();

					Log::write("PBR PX Asset Details:", $pbrPxAssetDetailsRaw, LogLevel::INFO);

					$pbrPxAssetDetails = $pbrPxAssetDetailsRaw['data'][0];

					// Extract information from response
					$tags = array_filter(preg_split('/[^A-Za-z0-9]/', $pbrPxAssetDetails['ename']));

					$type = AssetType::OTHER;
					if (str_starts_with($pbrPxAssetDetails['zips'], "HDRI")) {
						$type = AssetType::HDRI;
					} elseif (str_starts_with($pbrPxAssetDetails['zips'], "Textures")) {
						$type = AssetType::PBR_MATERIAL;
					} elseif (str_starts_with($pbrPxAssetDetails['zips'], "3D_Model")) {
						$type = AssetType::MODEL_3D;
					}

					// Decide on thumbnail
					$thumbnailUrlCandidates = explode("+", $pbrPxAssetDetails['img_url']);
					$thumbnailUrl = $this->mediaBaseUrl . $thumbnailUrlCandidates[0];
					foreach ($thumbnailUrlCandidates as $t) {
						if (str_contains($t, $this->thumbnailIdentifierString)) {
							$thumbnailUrl = $this->mediaBaseUrl . $t;
						}
					}

					Log::write("PBR PX Asset found", ["details" => $pbrPxAssetDetails, "type" => $type, "url" => $assetUrl], LogLevel::INFO);

					// Build asset
					$tmpCollection[] = new ScrapedAsset(
						id: NULL,
						creatorGivenId: null,
						title: $pbrPxAssetDetails['ename'],
						url: $assetUrl,
						date: new DateTime($pbrPxAsset['create_time']),
						type: $type,
						creator: $this->creator,
						rawThumbnailData: new WebItemReference(
							url: $thumbnailUrl
						)->fetch()->content,
						status: ScrapedAssetStatus::NEWLY_FOUND,
						tags: $tags,
					);

					$processedAssets += 1;
					if ($processedAssets > $maxAssets) {
						break;
					}
				}
			}

			$page += 1;
			Log::write("Processed page $page, found $assetsFoundThisIteration assets this iteration.", LogLevel::INFO);
		} while ($assetsFoundThisIteration > 0 && $processedAssets < $maxAssets);

		return $tmpCollection;
	}
}
