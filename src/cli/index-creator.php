<?php

use asset\AssetStatus;
use creator\Creator;
use log\LogLevel;
use misc\Database;
use log\Log;

require_once __DIR__ . '/../include/init.php';

// Pick a target creator
if (isset($argv[1])) {
	$creator = Creator::from(intval($argv[1]));
} else {
	$randomTargets = Creator::regularRefreshList();
	$randomIndex = array_rand($randomTargets);
	$creator = $randomTargets[$randomIndex];
}

Log::start(logName: "index-creator/" . $creator->slug(), level: LogLevel::INFO, writeToStdout: true);

$creatorFetcher = $creator->getIndexer();
if ($creatorFetcher === null) {
	Log::write("No indexer available for creator: " . $creator->name(), LogLevel::ERROR);
	exit(1);
}
$result = $creatorFetcher->runUpdate();
Log::write("Found " . sizeof($result->assets) . " new assets");
if (sizeof($result->assets) > 0) {
	Log::write("Writing new assets to DB:");
	foreach ($result->assets as $a) {
		Database::startTransaction();
		$a->status = AssetStatus::PENDING;	// Failsave in case the creator fetching function does not set it properly.
		Database::saveAssetToDatabase($a);
		Database::commitTransaction();
	}
	Log::write("Wrote " . sizeof($result->assets) . " new assets.");
}
