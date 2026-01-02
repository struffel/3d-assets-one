<?php

use asset\AssetQuery;
use asset\AssetStatus;
use asset\Sorting;
use creator\Creator;
use misc\Database;
use misc\Image;
use log\Log;
use log\LogLevel;

require_once __DIR__ . '/../include/init.php';

Log::start(logName: "activate-assets", level: LogLevel::INFO, writeToStdout: true);

// Read parameters
$maxNumberOfAssets = $argv[1] ?? 5;

if (isset($argv[2])) {
	$creators = [Creator::from(intval($argv[2]))];
} else {
	$creators = [];
}

$query = new AssetQuery(
	filterStatus: AssetStatus::PENDING,
	limit: $maxNumberOfAssets,
	sort: Sorting::RANDOM,
	filterCreator: $creators ?? NULL
);

$assetsToActivate = $query->execute();
foreach ($assetsToActivate->assets as $a) {

	Database::startTransaction();

	Log::write("Getting thumbnail for asset " . $a->id . " from url " . $a->thumbnailUrl);

	$creatorFetcher = $a->creator->getIndexer();
	$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);

	Image::buildAndUploadThumbnailsToBackblazeB2($a, $imageData);
	$a->status = AssetStatus::ACTIVE;
	Database::saveAssetToDatabase($a);

	Database::commitTransaction();
}
