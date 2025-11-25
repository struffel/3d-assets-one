<?php

namespace creator\indexing;

use asset\Asset;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Fetch;
use indexing\CreatorIndexer;
use misc\Log;

class CreatorIndexerPolyhaven extends CreatorIndexer
{
	protected static Creator $creator = Creator::POLYHAVEN;

	private static string $apiUrl = "https://api.polyhaven.com/assets";
	private static string $viewBaseUrl = "https://polyhaven.com/a/";
	private static string $thumbnailUrlPrefix = "https://cdn.polyhaven.com/asset_img/thumbs/";
	private static string $thumbnailUrlSuffix = ".png?height=512";
	private static array $typeMapping = [
		"0" => Type::HDRI,
		"1" => Type::PBR_MATERIAL,
		"2" => Type::MODEL_3D
	];

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		// Prepare asset collection
		$tmpCollection = new AssetCollection();
		$result = Fetch::fetchRemoteJson(self::$apiUrl);

		// Iterate through result
		foreach ($result as $key => $phAsset) {

			$url = self::$viewBaseUrl . $key;

			if (!in_array($url, $existingUrls)) {

				$tmpAsset = new Asset(
					id: NULL,
					url: $url,
					date: date('Y-m-d', $phAsset['date_published']),
					name: $phAsset['name'],
					tags: $phAsset['tags'],
					thumbnailUrl: self::$thumbnailUrlPrefix . $key . self::$thumbnailUrlSuffix,
					type: TYPE::from(self::$typeMapping[$phAsset['type']]),
					license: License::CC0,
					creator: Creator::POLYHAVEN,
					quirks: [Quirk::ADS]
				);

				$tmpCollection->assets[] = $tmpAsset;
				Log::write("Found new asset: " . $tmpAsset->url);
			}
		}

		return $tmpCollection;
	}
}
