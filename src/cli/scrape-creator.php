<?php

use asset\ScrapedAsset;
use asset\StoredAssetQuery;
use creator\Creator;
use creator\CreatorLogic;
use log\LogLevel;
use database\Database;
use log\Log;
use log\LogResult;
use thumbnail\Thumbnail;
use misc\StringUtil;

require_once __DIR__ . '/../include/init.php';

// Pick a target creator
if (isset($argv[1])) {
	$creator = Creator::fromAny($argv[1]);
} else {
	$randomTargets = Creator::regularRefreshList();
	$randomIndex = array_rand($randomTargets);
	$creator = $randomTargets[$randomIndex];
}

/**
 * @var Creator $creator
 */

// Start logging and determine the official run timestamp
$now = new DateTime();
$timestamp = $now->format('Y-m-d\TH-i-s-v');
Log::start(logName: "scrape-creator/" . $creator->slug() . "/" . $timestamp, level: LogLevel::INFO, writeToStdout: true);

Log::write("Selected creator:", $creator);

/** @var CreatorLogic $creatorLogic */
$creatorLogic = $creator->getLogic();

Log::write("Running update for creator", $creator);

// Get existing assets to provide a comparison
$query = new StoredAssetQuery();
$query->filterCreator = [$creator];
$query->filterStatus = NULL;
$query->limit = NULL;
$existingAssets = $query->execute();
Log::write("Found " . sizeof($existingAssets) . " existing assets for creator.");

// Get new assets using creator-specific method
// Passing in the list of existing URLs and
$newScrapedAssets = $creatorLogic->scrapeAssets($existingAssets);

// Perform post-processing on the results
foreach ($newScrapedAssets as $scrapedAsset) {
	if ($scrapedAsset === null) {
		continue;
	}
	// Expand and clean up the tag array
	$titleWords = preg_split('/\s+/', $scrapedAsset->title);
	if ($titleWords !== false) {
		$scrapedAsset->tags = array_merge($scrapedAsset->tags, $titleWords);
	}
	$scrapedAsset->tags[] = $creator->slug();
	$scrapedAsset->tags = StringUtil::filterTagArray($scrapedAsset->tags);
}

Log::write("Found " . sizeof($newScrapedAssets) . " new assets", $newScrapedAssets);

// Save new assets to DB
if (sizeof($newScrapedAssets) > 0) {

	/**
	 * @var ScrapedAsset $newScrapedAsset
	 */
	foreach ($newScrapedAssets as $newScrapedAsset) {

		// Validity checks
		if ($newScrapedAsset->creator == null || $newScrapedAsset->creator !== $creator) {
			Log::write("Skipping asset with mismatched creator: ", $newScrapedAsset, LogLevel::WARNING);
			continue;
		}

		// Convert scraped asset to stored asset
		$newStoredAsset = $newScrapedAsset->toStoredAsset();

		// Save stored asset to DB
		Database::startTransaction();

		$newStoredAsset->writeToDatabase();
		if ($newStoredAsset->id !== null && $newScrapedAsset->rawThumbnailData !== null) {
			Thumbnail::saveThumbnailVariations($newStoredAsset->id, $newScrapedAsset->rawThumbnailData);
			Log::write("Saved thumbnail for asset ", $newStoredAsset->id);
		}

		Database::commitTransaction();

		Log::write("Committed new asset to DB", $newStoredAsset);
	}
	Log::write("Wrote " . sizeof($newScrapedAssets) . " new assets.");
} else {
	Log::write("No new updates to write to DB.");
}

Thumbnail::deleteOrphanedThumbnails();

Log::stop(LogResult::OK);
