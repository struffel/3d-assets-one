<?php

use asset\AssetLogic;
use asset\AssetQuery;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$assetId = intval($_GET['id'] ?? "0");

$query = new AssetQuery(
	offset: 0,
	limit: 1,
	filterAssetId: [$assetId]
);
$result = $query->execute();
$asset = $result->assets[0] ?? null;

$url = $asset ? $asset->url : null;
if ($url) {
	header("Location: $url");
} else {
	http_response_code(404);
	die("3Dassets.one\nURL could not be resolved.");
}
AssetLogic::addAssetClickById($assetId);
