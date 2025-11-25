<?php

namespace creator\impl;

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Fetch;
use indexing\CreatorIndexer;

// textures.com

class CreatorIndexerTexturesCom extends CreatorIndexer
{

	protected static Creator $creator = Creator::TEXTURES_COM;

	private static string $apiBaseUrl = "https://www.textures.com/api/v1/texture/search?filter=free&page=";
	private static array $categoryMapping = [
		"114553" => Type::MODEL_3D,
		"114561" => Type::OTHER,
		"114548" => Type::PBR_MATERIAL,
		"114563" => Type::PBR_MATERIAL,
		"114570" => Type::MODEL_3D,
		"114558" => Type::PBR_MATERIAL,
		"114557" => Type::OTHER,
		"114552" => Type::HDRI,
		"23740" => Type::HDRI,
		"114568" => Type::OTHER,
		"114571" => Type::OTHER
	];

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();

		$page = 1;

		do {
			$apiData = Fetch::fetchRemoteJson(self::$apiBaseUrl . $page);

			$assetsFoundThisIteration = sizeof($apiData['data']);
			foreach ($apiData['data'] as $texComAsset) {

				$url = "https://textures.com/download/" . $texComAsset['filenameWithoutSet'] . "/" . $texComAsset['defaultPhotoSet']['id'];

				if (!in_array($url, $existingUrls)) {

					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $texComAsset['defaultPhotoSet']['titleThumbnail'],
						url: $url,
						thumbnailUrl: "https://textures.com/" . $texComAsset['picture'],
						date: $texComAsset['defaultPhotoSet']['createdAtUtc'],
						tags: array_filter(preg_split('/[^A-Za-z0-9]/', $texComAsset['defaultPhotoSet']['titleThumbnail'])),
						type: self::$categoryMapping[$texComAsset['defaultCategoryId']] ?? Type::OTHER,
						license: License::CUSTOM,
						creator: Creator::TEXTURES_COM,
						quirks: [Quirk::SIGNUP_REQUIRED],
						status: AssetStatus::PENDING
					);
				}
			}

			$page += 1;
		} while ($assetsFoundThisIteration > 0 && $page < 20 /* Failsafe */);

		return $tmpCollection;
	}
}
