<?php

use asset\AssetStatus;
use asset\Asset;
use creator\Creator;
use log\LogLevel;
use misc\Database;
use log\Log;
use misc\Image;

require_once __DIR__ . '/../include/init.php';

Log::start(logName: "index-creator/" . $creator->slug(), level: LogLevel::INFO, writeToStdout: true);


// Pick a target creator
if (isset($argv[1])) {
	$creator = Creator::from(intval($argv[1]));

	// Pick how many assets to fetch
	if (isset($argv[2])) {
		$maxAssets = intval($argv[2]);
		Log::write("Limiting to " . $maxAssets . " assets.");
	}
} else {
	$randomTargets = Creator::regularRefreshList();
	$randomIndex = array_rand($randomTargets);
	$creator = $randomTargets[$randomIndex];
	$maxAssets = null;
}

Log::write("Selected creator:", $creator);
Log::write("Selected max assets:", $maxAssets);

$creatorFetcher = $creator->getIndexer();

if ($creatorFetcher === null) {
	Log::write("No indexer available for creator", $creator, LogLevel::ERROR);
	exit(1);
}

Log::write("Running update for creator");

$result = $creatorFetcher->runUpdate();

Log::write("Found " . sizeof($result->assets) . " new assets", $result);

if (sizeof($result->assets) > 0) {

	/**
	 * @var Asset $a
	 */
	foreach ($result->assets as $a) {
		$a->status = AssetStatus::ACTIVE;
		Database::startTransaction();

		// Save asset to DB
		Database::saveAssetToDatabase($a);

		// Add the missing id to the asset object
		$a->id = Database::runQuery("SELECT assetId FROM Asset WHERE assetUrl = ?;", [$a->url])->fetch_assoc()['assetId'];

		if ($a->rawThumbnailData) {
			Image::saveThumbnail($a->id, $a->rawThumbnailData);
			Log::write("Saved thumbnail for asset ", $a->id);
		} else {
			throw new Exception("No thumbnail data present for asset " . $a->id);
		}
		Database::commitTransaction();
	}
	Log::write("Wrote " . sizeof($result->assets) . " new assets.");
} else {
	Log::write("No new updates to write to DB.");
}
