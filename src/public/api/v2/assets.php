<?php

use asset\AssetLogic;
use asset\AssetQuery;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$query = AssetQuery::fromHttpGet();
$assets = AssetLogic::getAssets($query);

$thumbnailFormat = match ($_GET['thumbnail-format'] ?? "") {
	"256-JPG-FFFFFF" => "256-JPG-FFFFFF",
	"128-JPG-FFFFFF" => "128-JPG-FFFFFF",
	"256-PNG" => "256-PNG",
	"128-PNG" => "128-PNG",
	default => "256-JPG-FFFFFF",
};

for ($i = 0; $i < sizeof($assets->assets); $i++) {
	$id = $assets->assets[$i]->id;
	$assets->assets[$i]->thumbnailUrl = getenv("3D1_CDN") . "/thumbnail/$thumbnailFormat/$id.jpg";
	unset($assets->assets[$i]->status);
	unset($assets->assets[$i]->lastSuccessfulValidation);
}

echo json_encode($assets);
