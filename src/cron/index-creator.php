<?php

use asset\AssetStatus;
use creator\Creator;
use misc\Database;
use misc\Log;

require_once __DIR__ . '/../include/init.php';
Log::initialize("cron");

// Pick a target creator
if (isset($argv[1])) {
	$creator = Creator::from(intval($argv[1]));
} else {
	$randomTargets = Creator::regularRefreshList();
	$randomIndex = array_rand($randomTargets);
	$creator = $randomTargets[$randomIndex];
}

Log::write("Refreshing Creator: " . $creator->slug());

$creatorFetcher = $creator->getIndexer();
Log::write("Created creator object.");
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
