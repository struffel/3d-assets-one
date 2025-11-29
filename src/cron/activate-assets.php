<?php

use asset\AssetQuery;
use asset\AssetStatus;
use asset\Sorting;
use creator\Creator;
use misc\Database;
use misc\Image;
use misc\Log;

require_once __DIR__ . '/../include/init.php';

$maxNumberOfAssets = max(1, intval($_GET['number'] ?? 0));

if (isset($_GET['creatorId'])) {
	$creators = [Creator::from(intval($_GET['creatorId']))];
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
