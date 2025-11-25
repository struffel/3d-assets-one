<?php

namespace indexing;

use asset\Asset;
use asset\AssetLogic;
use asset\AssetQuery;
use AssetCollection;
use creator\Creator;
use Fetch;
use misc\Database;
use misc\Log;
use Throwable;

abstract class CreatorIndexer
{

	// Class variables
	protected static Creator $creator;

	// General functions

	protected static final function getFetchingState(string $key): ?string
	{
		return Database::runQuery("SELECT * FROM FetchingState WHERE creatorId = ? AND stateKey = ?", [self::$creator->value, $key])->fetch_assoc()['stateValue'] ?? NULL;
	}

	protected static final function saveFetchingState(string $key, string $value): void
	{
		Database::runQuery("REPLACE INTO FetchingState (creatorId,stateKey,stateValue) VALUES (?,?,?);", [self::$creator->value, $key, $value]);
	}

	public static final function runUpdate(): AssetCollection
	{

		// Get existing URLs
		$query = new AssetQuery();
		$query->filterCreator = [self::$creator];
		$query->filterStatus = NULL;
		$query->limit = NULL;
		$result = AssetLogic::getAssets($query);
		$existingUrls = [];
		foreach ($result->assets as $asset) {
			$existingUrls[] = $asset->url;
		}
		Log::write("Found " . sizeof($existingUrls) . " existing URLs for creator.");

		// Get new assets using creator-specific method
		// Passing in the list of existing URLs and
		$newAssetCollection = static::findNewAssets($existingUrls);

		// Perform post-processing on the results
		for ($i = 0; $i < sizeof($newAssetCollection->assets); $i++) {
			// Expand and clean up the tag array
			$newAssetCollection->assets[$i]->tags = array_merge($newAssetCollection->assets[$i]->tags, preg_split('/\s+/', $newAssetCollection->assets[$i]->name));
			$newAssetCollection->assets[$i]->tags[] = self::$creator->slug();
			$newAssetCollection->assets[$i]->tags = AssetLogic::filterTagArray($newAssetCollection->assets[$i]->tags);
		}

		return $newAssetCollection;
	}

	// Creator-specific functions

	public static function fetchThumbnailImage(string $url): string
	{
		return Fetch::fetchRemoteData($url);
	}

	public static function processUrl(string $url): string
	{
		return $url;
	}

	public static function validateAsset(Asset $asset): bool
	{
		try {
			Fetch::fetchRemoteData($asset->url);
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	public abstract static function findNewAssets(array $existingUrls): AssetCollection;
}
