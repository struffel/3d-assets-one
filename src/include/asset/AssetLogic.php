<?php

namespace asset;

use creator\Creator;
use asset\Quirk;
use DateTime;
use misc\Database;
use misc\Log;

class AssetLogic
{

	public static function filterTagArray(array $inputArray)
	{
		// Initialize an empty result array
		$resultArray = array();

		// Loop through each element in the input array
		foreach ($inputArray as $element) {
			// Trim the element and convert it to lowercase
			$filteredElement = strtolower(trim($element));

			// Split the element into multiple elements by space
			$splitElements = preg_split('/\s+/', $filteredElement);

			// Loop through the split elements and remove non-alphanumeric characters
			foreach ($splitElements as $splitElement) {
				// Remove non-alphanumeric characters using a regular expression
				$filteredSplitElement = preg_replace('/[^a-z0-9]/', '', $splitElement);

				// Check if the filtered element is not empty and add it to the result array
				if (!empty($filteredSplitElement)) {
					$resultArray[] = $filteredSplitElement;
				}
			}
		}

		return array_unique($resultArray);
	}

	public static function getUrlById(string $assetId): string
	{
		$sql = "SELECT assetUrl,creatorId FROM Asset WHERE assetId = ? LIMIT 1;";
		$sqlResult = Database::runQuery($sql, [intval($assetId)]);

		$row = $sqlResult->fetch_assoc();

		$creator = Creator::from($row['creatorId']);
		$creatorFetcher = $creator->getIndexer();

		return $creatorFetcher->processUrl($row['assetUrl']);
	}

	public static function addAssetClickById(int $assetId)
	{
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		Database::runQuery($sql, [intval($assetId)]);
	}

	public static function saveAssetToDatabase(Asset $asset)
	{

		Log::stepIn(__FUNCTION__);

		if ($asset->id) {
			Log::write("Updating Asset with id: " . $asset->id);

			// Base Asset
			$sql = "UPDATE Asset SET assetName=?,assetActive=?,assetUrl=?,assetThumbnailUrl=?,assetDate=?,licenseId=?,typeId=?,creatorId=?,lastSuccessfulValidation=? WHERE assetId = ?";
			$parameters = [$asset->name, $asset->status->value, $asset->url, $asset->thumbnailUrl, $asset->date, $asset->license->value, $asset->type->value, $asset->creator->value, $asset->lastSuccessfulValidation, $asset->id];
			Database::runQuery($sql, $parameters);

			// Tags
			Database::runQuery("DELETE FROM Tag WHERE assetId = ?", [$asset->id]);
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES (?,?);";
				$parameters = [$asset->id, $tag];
				Database::runQuery($sql, $parameters);
			}

			// Quirks

			Database::runQuery("DELETE FROM Quirk WHERE assetId = ?", [$asset->id]);
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES (?,?);";
				$parameters = [$asset->id, $quirk->value];
				Database::runQuery($sql, $parameters);
			}
		} else {
			Log::write("Inserting new asset with url:" . $asset->url);

			// Base Asset
			$sql = "INSERT INTO Asset (assetId, assetActive,assetName, assetUrl, assetThumbnailUrl, assetDate, assetClicks, licenseId, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$parameters = [$asset->status->value, $asset->name, $asset->url, $asset->thumbnailUrl, $asset->date, 0, $asset->license->value, $asset->type->value, $asset->creator->value];
			Database::runQuery($sql, $parameters);

			// Tags
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url, $tag];
				Database::runQuery($sql, $parameters);
			}

			// Quirks
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url, $quirk->value];
				Database::runQuery($sql, $parameters);
			}
		}

		Log::stepOut(__FUNCTION__);
		return $asset;
	}
}
