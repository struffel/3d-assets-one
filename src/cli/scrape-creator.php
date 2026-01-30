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

// Show CLI syntax
if ($argc > 2 || in_array($argv[1] ?? '', ['-h', '--help', 'help', "h"])) {
	echo "Usage: php scrape-creator.php [creator-slug|creator-id] [force]
Not setting a creator will pick a random one from the regular scraping targets.
The force argument disables graceful backoff on errors.\n";
	exit(1);
}

// Start logging and determine the official run timestamp
$now = new DateTime();
$timestamp = $now->format('Y-m-d\TH-i-s-v');
Log::start(logName: "scrape-creator/"  . $timestamp, level: LogLevel::INFO, writeToStdout: true);

// Determine backoff behavior
$force = isset($argv[2]) && strtolower($argv[2]) === 'force';
Log::write("Forceful mode?", $force, LogLevel::DEBUG);

// Pick a target creator
if (isset($argv[1])) {
	$creator = Creator::fromAny($argv[1]);
} else {
	$creator = Creator::randomScrapingTarget(!$force);
}

/** 
 * @var Creator $creator
 * @var CreatorLogic $creatorLogic */
$creatorLogic = $creator->getLogic();
Log::write("Loaded logic and starting to scrape for creator", $creator->name, LogLevel::INFO);

// Increment failure counter (will be reset on success)
$creator->incrementFailedAttempts($now);

// Get existing assets to provide a comparison
$query = new StoredAssetQuery();
$query->filterCreator = [$creator];
$query->filterStatus = NULL;
$query->limit = NULL;
$existingAssets = $query->execute();
Log::write("Found " . sizeof($existingAssets) . " existing assets for creator.", LogLevel::INFO);

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

Log::write("Found " . sizeof($newScrapedAssets) . " new assets", $newScrapedAssets, LogLevel::INFO);

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

		Log::write("Committed new asset to DB", $newStoredAsset, LogLevel::INFO);
	}
	Log::write("Wrote " . sizeof($newScrapedAssets) . " new assets.", LogLevel::INFO);
} else {
	Log::write("No new updates to write to DB.", LogLevel::INFO);
}

$creator->resetFailedAttempts($now);

Thumbnail::deleteOrphanedThumbnails();

Log::stop(true);
