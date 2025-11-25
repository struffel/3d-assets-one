<?php

namespace creator\impl;

use asset\Asset;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use AssetCollection;
use creator\Creator;
use creator\Quirk;
use Exception;
use Fetch;
use indexing\CreatorIndexer;
use misc\Html;
use Rct567\DomQuery\DomQuery;

// poliigon

class CreatorIndexerPoliigon extends CreatorIndexer
{

	protected static Creator $creator = Creator::POLIIGON;

	private static string $baseUrl = "https://www.poliigon.com";
	private static string $searchBaseUrl = "https://www.poliigon.com/search/free?page=";
	private static array $urlTypeRegex = [
		'/\/texture\//i' => Type::PBR_MATERIAL,
		'/\/model\//i' => Type::MODEL_3D,
		'/\/hdri\//i' => Type::HDRI,
	];

	private static function extractId($url)
	{
		return end(explode('/', rtrim($url, '/')));
	}

	private static function isInExistingUrls($url, $existingUrls)
	{
		// Extracting ID from the input link

		$id = self::extractId($url);
		$existingIds = [];
		foreach ($existingUrls as $eU) {
			$existingIds[] = self::extractId($eU);
		}

		return in_array($id, $existingIds);
	}

	public static function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();

		$page = 1;

		do {
			$rawHtml = Fetch::fetchRemoteData(self::$searchBaseUrl . $page);
			$dom = Html::domObjectFromHtmlString($rawHtml);
			$domQuery = new DomQuery($dom);

			$assetBoxDomElements = $domQuery->find('a.asset-box');
			$assetBoxesFoundThisIteration = sizeof($assetBoxDomElements);
			foreach ($assetBoxDomElements as $assetBox) {

				$urlPath = $assetBox->attr('href');
				$url = self::$baseUrl . $urlPath;
				if (!self::isInExistingUrls($url, $existingUrls)) {

					$name = $assetBox->find('img')->attr('alt');

					$type = NULL;

					foreach (self::$urlTypeRegex as $regex => $typeId) {
						if (preg_match($regex, $urlPath)) {
							$type = Type::from($typeId);
						}
					}
					if (!$type) {
						throw new Exception("Could not find type from urlPath '$urlPath'");
					}

					$tmpCollection->assets[] = new Asset(
						id: NULL,
						name: $name,
						url: $url,
						thumbnailUrl: $assetBox->find('img')->attr('src'),
						date: date("Y-m-d"),
						tags: preg_split('/\s|,/', $name),
						type: $type,
						license: License::CUSTOM,
						creator: Creator::POLIIGON,
						quirks: [Quirk::SIGNUP_REQUIRED],
						status: AssetStatus::PENDING
					);
				}
			}

			$page += 1;
		} while ($assetBoxesFoundThisIteration > 0 && $page < 20 /* Failsafe */);

		return $tmpCollection;
	}
}
