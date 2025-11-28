<?php

use asset\AssetLogic;
use asset\AssetQuery;
use asset\AssetStatus;
use asset\License;
use asset\Type;
use creator\Creator;
use asset\Quirk;
use misc\Database;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

if ($_POST['id'] ?? false) {
	Database::startTransaction();
	$query = new AssetQuery(filterAssetId: [$_POST['id']], filterStatus: NULL);
	$assets = AssetLogic::getAssets($query);
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
		$a->type = Type::from(intval($_POST['type']));
	}

	if (isset($_POST['license'])) {
		$a->license = License::from(intval($_POST['license']));
	}

	if (isset($_POST['creator'])) {
		$a->creator = Creator::from(intval($_POST['creator']));
	}

	if (isset($_POST['status'])) {
		$a->status = AssetStatus::from(intval($_POST['status']));
	}

	// Arrays

	if (isset($_POST['tagString'])) {
		$a->tags =  array_filter(preg_split('/[^A-Za-z0-9]/', $_POST['tagString']));
	}

	if (isset($_POST['quirks'])) {
		$quirks = [];
		foreach ($_POST['quirks'] as $q) {
			$quirks[] = Quirk::from(intval($q));
		}
		$a->quirks = $quirks;
	}

	AssetLogic::saveAssetToDatabase($a);
	Database::commitTransaction();
	echo "<div remove-me='1s'>OK</div>";
} else {
	http_response_code(400);
	echo "<div>No ID.</div>";
}
