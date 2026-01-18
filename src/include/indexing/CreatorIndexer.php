<?php

namespace indexing;

use asset\Asset;
use asset\AssetCollection;
use asset\AssetQuery;
use asset\AssetStatus;
use creator\Creator;
use Exception;

use fetch\WebItemReference;
use misc\Database;
use log\Log;
use misc\StringUtil;
use Throwable;

abstract class CreatorIndexer
{

	// Class variables
	protected Creator $creator {
		get {
			return $this->creator;
		}
		/*set(Creator $value) {
			throw new Exception("Cannot change creator of indexer.");
		}*/
	}

	private int $maxAssetsPerRun = 5;

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
		$result = $query->execute();
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
			$newAssetCollection->assets[$i]->tags[] = $this->creator->slug();
			$newAssetCollection->assets[$i]->tags = StringUtil::filterTagArray($newAssetCollection->assets[$i]->tags);
		}

		return $newAssetCollection;
	}

	// Creator-specific functions

	public function processUrl(string $url): string
	{
		return $url;
	}

	public function validateAsset(Asset $asset): bool
	{
		try {
			$result = new WebItemReference(url: $asset->url)->fetch();
			return $result->httpStatusCode === 200 && $result->content !== null;
		} catch (Throwable $e) {
			return false;
		}
	}

	public abstract function findNewAssets(array $existingUrls): AssetCollection;
}
