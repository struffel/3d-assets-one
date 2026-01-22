<?php

use asset\StoredAssetQuery;
use asset\ScrapedAssetStatus;
use asset\CommonLicense;
use asset\AssetType;
use creator\Creator;

use database\Database;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

if ($_POST['id'] ?? false) {
	Database::startTransaction();
	$query = new StoredAssetQuery(filterAssetId: [$_POST['id']], filterStatus: NULL);
	$assets = $query->execute();
	$a = $assets->assets[0];

	if (!$a) {
		throw new Exception("No asset found.", 1);
	}

	// Basic parameters

	if (isset($_POST['name'])) {
		$a->name = $_POST['name'];
	}

	if (isset($_POST['url'])) {
		$a->url = $_POST['url'];
	}

	if (isset($_POST['thumbnailUrl'])) {
		$a->thumbnailUrl = $_POST['thumbnailUrl'];
	}

	if (isset($_POST['date'])) {
		$a->date = $_POST['date'];
	}

	// Enums

	if (isset($_POST['type'])) {
		$a->type = AssetType::from(intval($_POST['type']));
	}

	if (isset($_POST['license'])) {
		$a->license = CommonLicense::from(intval($_POST['license']));
	}

	if (isset($_POST['creator'])) {
		$a->creator = Creator::from(intval($_POST['creator']));
	}

	if (isset($_POST['status'])) {
		$a->status = ScrapedAssetStatus::from(intval($_POST['status']));
	}

	// Arrays

	if (isset($_POST['tagString'])) {
		$a->tags =  array_filter(preg_split('/[^A-Za-z0-9]/', $_POST['tagString']));
	}

	Database::saveAssetToDatabase($a);
	Database::commitTransaction();
	echo "<div remove-me='1s'>OK</div>";
} else {
	http_response_code(400);
	echo "<div>No ID.</div>";
}
