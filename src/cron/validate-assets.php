<?php

require_once __DIR__ . '/../include/init.php';

use asset\AssetQuery;
use asset\AssetStatus;
use asset\Sorting;
use creator\Creator;
use log\LogLevel;
use misc\Database;
use misc\Log;

$maxNumberOfAssets = max(2, intval($_GET['number'] ?? 6));
$assetsToCheck = [];

// Select creator
if (isset($_GET['creatorId'])) {
	$filterCreator = [Creator::from(intval($_GET['creatorId']))];
} else {
	$filterCreator = [];
}

// Get active assets to check
$assetsToCheck = array_merge($assetsToCheck, (new AssetQuery(
	limit: $maxNumberOfAssets / 2,
	filterStatus: AssetStatus::ACTIVE,
	sort: Sorting::OLDEST_VALIDATION_SUCCESS,
	filterCreator: $filterCreator
))->execute()->assets);

// Get assets that failed their validation
$assetsToCheck = array_merge($assetsToCheck, (new AssetQuery(
	limit: $maxNumberOfAssets / 2,
	filterStatus: AssetStatus::VALIDATION_FAILED_RECENTLY,
	sort: Sorting::RANDOM,
	filterCreator: $filterCreator
))->execute()->assets);

foreach ($assetsToCheck as $asset) {

	Database::startTransaction();

	Log::write("Testing asset " . $asset->id);
	Log::write("Asset made by " . $asset->creator->slug());

	$creatorFetcher = $asset->creator->getIndexer();
	$currentDateTime = new DateTime();

	// Test if the asset is still valid
	try {
		$testResult = $creatorFetcher->validateAsset($asset);
	} catch (\Throwable $th) {
		Log::write("Skipping this asset due to exception: " . $th->getMessage(), LogLevel::ERROR);
		continue;
	}

	if ($testResult) {
		$asset->lastSuccessfulValidation = $currentDateTime;
		$asset->status = AssetStatus::ACTIVE;
		Log::write("Validation OK");
	} else {

		// If the asset is invalid and was already invalid before the test, check if its last successful validation was 2 or more days ago.
		// In that case it is considered failed permanently and will not be added to the validation rotation again.
		if ($asset->status == AssetStatus::ACTIVE | $currentDateTime->diff($asset->lastSuccessfulValidation)->d < 2) {
			$asset->status = AssetStatus::VALIDATION_FAILED_RECENTLY;
			Log::write("Validation Failed (Recently)", LogLevel::WARNING);
		} else {
			$asset->status = AssetStatus::VALIDATION_FAILED_PERMANENTLY;
			Log::write("Validation Failed (Permanently)", LogLevel::WARNING);
		}
	}
	Database::saveAssetToDatabase($asset);

	Database::commitTransaction();
}
