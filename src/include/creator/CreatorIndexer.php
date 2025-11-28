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
	protected Creator $creator;

	// General functions

	protected final function getFetchingState(string $key): ?string
	{
		return Database::runQuery("SELECT * FROM FetchingState WHERE creatorId = ? AND stateKey = ?", [$this->creator->value, $key])->fetch_assoc()['stateValue'] ?? NULL;
	}

	protected final function saveFetchingState(string $key, string $value): void
	{
		Database::runQuery("REPLACE INTO FetchingState (creatorId,stateKey,stateValue) VALUES (?,?,?);", [$this->creator->value, $key, $value]);
	}

	public final function runUpdate(): AssetCollection
	{

		// Get existing URLs
		$query = new AssetQuery();
		$query->filterCreator = [$this->creator];
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
		$newAssetCollection = $this->findNewAssets($existingUrls);

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

	public function fetchThumbnailImage(string $url): string
	{
		return Fetch::fetchRemoteData($url);
	}

	public function processUrl(string $url): string
	{
		return $url;
	}

	public function validateAsset(Asset $asset): bool
	{
		try {
			Fetch::fetchRemoteData($asset->url);
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	public abstract function findNewAssets(array $existingUrls): AssetCollection;
}
