<?php

use asset\AssetLogic;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$assetId = intval($_GET['id'] ?? "0");

$url = AssetLogic::getUrlById($assetId);
if ($url) {
	header("Location: $url");
} else {
	http_response_code(404);
	die("3Dassets.one\nURL could not be resolved.");
}
AssetLogic::addAssetClickById($assetId);
