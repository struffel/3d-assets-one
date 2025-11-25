<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use Fetch;
use indexing\CreatorIndexer;
use misc\Image;

// amd materialx

class CreatorIndexerAmdMaterialX extends CreatorIndexer
{

	protected static Creator $creator = Creator::GPUOPENMATLIB;

	private static string $apiUrl = 'https://api.matlib.gpuopen.com/api/materials/?limit=50&ordering=-published_date&status=Published&updateKey=1&offset=0';
	private static string $tagApiUrl = 'https://api.matlib.gpuopen.com/api/tags/';
	private static string $urlTemplate = 'https://matlib.gpuopen.com/main/materials/all?material=#ID#';
	private static string $previewImageTemplate = 'https://image.matlib.gpuopen.com/#ID#.jpeg';
	private static string $excludeTitleRegex = "/^TH: /";
	private static int $maxAssetsPerRound = 1;

	public static  function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$targetUrl = self::$apiUrl;
		// Limit number of assets to avoid excessive calls to the tag API
		$countAssets = 0;
		do {
			$apiJson = Fetch::fetchRemoteJson($targetUrl);
			foreach ($apiJson['results'] as $amdAsset) {
				if ($countAssets < self::$maxAssetsPerRound && !preg_match(self::$excludeTitleRegex, $amdAsset['title'])) {

					$url = str_replace('#ID#', $amdAsset['id'], self::$urlTemplate);
					if (!in_array($url, $existingUrls)) {

						$tags = [];

						foreach ($amdAsset['tags'] as $t) {
							$tags[] = Fetch::fetchRemoteJson(self::$tagApiUrl . $t)['title'];
						}

						$tmpAsset = new Asset(
							id: NULL,
							url: $url,
							name: $amdAsset['title'],
							date: $amdAsset['published_date'],
							tags: $tags,
							type: Type::PBR_MATERIAL,
							license: License::APACHE_2_0,
							creator: Creator::GPUOPENMATLIB,
							thumbnailUrl: str_replace(
								'#ID#',
								$amdAsset['renders_order'][0],
								self::$previewImageTemplate
							)
						);

						$tmpCollection->assets[] = $tmpAsset;
						$countAssets++;
					}
				}
			}
			$targetUrl = $apiJson['next'] ?? null;
		} while ($targetUrl != null && $countAssets < self::$maxAssetsPerRound);

		return $tmpCollection;
	}

	public static function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(Fetch::fetchRemoteData($url), 10, 10, 0.015);
	}
}
