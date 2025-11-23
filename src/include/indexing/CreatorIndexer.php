<?php

namespace indexing;

use creator\Creator;
use misc\Database;

abstract class CreatorIndexer
{

	// Class variables
	public readonly Creator $target;

	// General functions

	protected final function getFetchingState(string $key): ?string
	{
		return Database::runQuery("SELECT * FROM FetchingState WHERE creatorId = ? AND stateKey = ?", [$this->creator->value, $key])->fetch_assoc()['stateValue'] ?? NULL;
	}

	protected final function saveFetchingState(string $key, string $value): void
	{
		Database::runQuery("REPLACE INTO FetchingState (creatorId,stateKey,stateValue) VALUES (?,?,?);", [$this->creator->value, $key, $value]);
	}

	protected final function getConfig(): array
	{
		return json_decode(file_get_contents("../creators/" . $this->indexingTarget->value . ".json"), associative: TRUE);
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
		LogLogic::write("Found " . sizeof($existingUrls) . " existing URLs for creator.");

		// Get new assets using creator-specific method
		// Passing in the list of existing URLs and
		$newAssetCollection = $this->findNewAssets($existingUrls, $this->getConfig(), true);

		// Perform post-processing on the results
		for ($i = 0; $i < sizeof($newAssetCollection->assets); $i++) {
			// Expand and clean up the tag array
			$newAssetCollection->assets[$i]->tags = array_merge($newAssetCollection->assets[$i]->tags, preg_split('/\s+/', $newAssetCollection->assets[$i]->name));
			$newAssetCollection->assets[$i]->tags[] = $this->creator->slug();
			$newAssetCollection->assets[$i]->tags = AssetLogic::filterTagArray($newAssetCollection->assets[$i]->tags);
		}

		return $newAssetCollection;
	}

	// Creator-specific functions

	public function fetchThumbnailImage(string $url): string
	{
		return FetchLogic::fetchRemoteData($url);
	}

	public function processUrl(string $url): string
	{
		return $url;
	}

	public function validateAsset(Asset $asset): bool
	{
		try {
			FetchLogic::fetchRemoteData($asset->url);
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	public abstract function findNewAssets(array $existingUrls, array $config): AssetCollection;
}
