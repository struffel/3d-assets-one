<?php

use asset\StoredAssetQuery;
use asset\ScrapedAssetStatus;
use asset\CommonLicense;
use asset\AssetType;
use creator\Creator;
use database\Database;

use asset\StoredAsset;
use asset\StoredAssetStatus;
use blocks\EditorRow;
use misc\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header('Cache-Control: no-store');

Auth::requireAuth();

if ($_POST['id'] ?? false) {

	Database::startTransaction();

	// Get the existing asset
	$query = new StoredAssetQuery(filterAssetId: [$_POST['id']], filterStatus: NULL);
	$assets = $query->execute();

	/** @var StoredAsset $asset */
	$asset = $assets[0] ?? throw new Exception("The update target was not found.");

	// Basic parameters
	if (isset($_POST['title'])) {
		$asset->title = $_POST['title'];
	}

	if (isset($_POST['url'])) {
		$asset->url = $_POST['url'];
	}

	if (isset($_POST['date'])) {
		$asset->date = new DateTime($_POST['date']);
	}

	// Enums
	if (isset($_POST['type'])) {
		$asset->type = AssetType::from(intval($_POST['type']));
	}

	if (isset($_POST['status'])) {
		$asset->status = StoredAssetStatus::from(intval($_POST['status']));
	}

	// Arrays
	if (isset($_POST['tagString'])) {
		$tags = preg_split('/[^A-Za-z0-9]/', $_POST['tagString']);
		if ($tags === false) {
			$tags = [];
		}
		$asset->tags = array_filter($tags);
	}

	$asset->writeToDatabase();
	Database::commitTransaction();
	EditorRow::render($asset, true);
} else {
	http_response_code(400);
	echo "<div>No update target provided.</div>";
}
