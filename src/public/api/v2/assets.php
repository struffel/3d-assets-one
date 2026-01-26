<?php

use asset\StoredAssetQuery;
use thumbnail\ThumbnailFormat;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$query = StoredAssetQuery::fromHttpGet();
$assets = $query->execute();

$output = [];

$thumbnailFormat = ThumbnailFormat::tryFrom($_GET['thumbnailFormat'] ?? '') ?? ThumbnailFormat::PNG_256;

foreach ($assets as $asset) {
	$output[] = $asset->apiRepresentation($thumbnailFormat);
}


echo json_encode($output);
