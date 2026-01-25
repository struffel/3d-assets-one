<?php

require_once __DIR__ . '/../include/init.php';

use asset\StoredAssetQuery;
use asset\ScrapedAssetStatus;
use asset\AssetSorting;
use creator\Creator;
use log\LogLevel;
use database\Database;
use log\Log;

Log::start(logName: "validate-assets", level: LogLevel::INFO, writeToStdout: true);

// Read parameters
$maxNumberOfAssets = $argv[1] ?? 5;

if (isset($argv[2])) {
	$filterCreator = [Creator::from(intval($argv[2]))];
} else {
	$filterCreator = [];
}

Log::write("Validating up to " . $maxNumberOfAssets . " assets.");

Log::write("Looking for assets to validate.");

$assetsToCheck = [];

// Get active assets to check
$assetsToCheck = array_merge($assetsToCheck, (new StoredAssetQuery(
	limit: $maxNumberOfAssets / 2,
	filterStatus: ScrapedAssetStatus::ACTIVE,
	sort: AssetSorting::OLDEST_VALIDATION_SUCCESS,
	filterCreator: $filterCreator
))->execute()->assets);

// Get assets that failed their validation
$assetsToCheck = array_merge($assetsToCheck, (new StoredAssetQuery(
	limit: $maxNumberOfAssets / 2,
	filterStatus: ScrapedAssetStatus::VALIDATION_FAILED_RECENTLY,
	sort: AssetSorting::RANDOM,
	filterCreator: $filterCreator
))->execute()->assets);


Log::write("Found " . sizeof($assetsToCheck) . " assets to validate.");

foreach ($assetsToCheck as $asset) {

	Database::startTransaction();

	Log::write("Testing asset", $asset);

	$creatorFetcher = $asset->creator->getIndexer();
	$currentDateTime = new DateTime();

	// Test if the asset is still valid
	try {
		$testResult = $creatorFetcher->validateAsset($asset);
	} catch (\Throwable $th) {
		Log::write("Skipping this asset due to exception", $th, LogLevel::ERROR);
		continue;
	}

	if ($testResult) {
		$asset->lastSuccessfulValidation = $currentDateTime;
		$asset->status = ScrapedAssetStatus::ACTIVE;
		Log::write("Validation OK");
	} else {

		// If the asset is invalid and was already invalid before the test, check if its last successful validation was 2 or more days ago.
		// In that case it is considered failed permanently and will not be added to the validation rotation again.
		if ($asset->status == ScrapedAssetStatus::ACTIVE | $currentDateTime->diff($asset->lastSuccessfulValidation)->d < 2) {
			$asset->status = ScrapedAssetStatus::VALIDATION_FAILED_RECENTLY;
			Log::write("Validation Failed (Recently)", LogLevel::WARNING);
		} else {
			$asset->status = ScrapedAssetStatus::VALIDATION_FAILED_PERMANENTLY;
			Log::write("Validation Failed (Permanently)", LogLevel::WARNING);
		}
	}

	Database::saveAssetToDatabase($asset);

	Database::commitTransaction();
}
