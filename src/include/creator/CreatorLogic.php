<?php

namespace creator;

use asset\Asset;
use asset\ScrapedAssetCollection;
use asset\StoredAssetCollection;
use asset\StoredAssetQuery;
use asset\ScrapedAssetStatus;
use creator\Creator;
use Exception;

use fetch\WebItemReference;
use database\Database;
use log\Log;
use misc\StringUtil;
use Throwable;

abstract class CreatorLogic
{

	// Class variables
	protected Creator $creator {
		get {
			return $this->creator;
		}
		set(Creator $value) {
			throw new Exception("Cannot change creator of indexer.");
		}
	}

	private int $maxAssetsPerRun;

	// General functions

	protected final function getCreatorState(string $key): ?string
	{
		return Database::runQuery("SELECT * FROM IndexingState WHERE creatorId = ? AND stateKey = ?", [$this->creator->value, $key])->fetch_assoc()['stateValue'] ?? NULL;
	}

	protected final function setCreatorState(string $key, string $value): void
	{
		Database::runQuery("REPLACE INTO IndexingState (creatorId,stateKey,stateValue) VALUES (?,?,?);", [$this->creator->value, $key, $value]);
	}

	// Creator-specific functions

	public function postprocessUrl(string $url): string
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

	/**
	 * 
	 * @param StoredAssetCollection $existingAssets 
	 * @return ScrapedAssetCollection 
	 */
	public abstract function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection;
}
