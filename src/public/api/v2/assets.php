<?php

use asset\StoredAssetQuery;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$query = StoredAssetQuery::fromHttpGet();
$assets = $query->execute();

$thumbnailFormat = match ($_GET['thumbnail-format'] ?? "") {
	"256-JPG-FFFFFF" => "256-JPG-FFFFFF",
	"128-JPG-FFFFFF" => "128-JPG-FFFFFF",
	"256-PNG" => "256-PNG",
	"128-PNG" => "128-PNG",
	default => "256-JPG-FFFFFF",
};

for ($i = 0; $i < sizeof($assets); $i++) {
	$id = $assets[$i]->id;
	$assets[$i]->thumbnailUrl = $_ENV["3D1_CDN"] . "/thumbnail/$thumbnailFormat/$id.jpg";
	unset($assets[$i]->status);
	unset($assets[$i]->lastSuccessfulValidation);
}

echo json_encode($assets);
