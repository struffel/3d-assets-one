<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/strings.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/json.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/images.php';

	initializeLog("activateAssets");

	$query = new AssetQuery();
	$query->filter->active=false;
	$query->include->internal=true;
	$query->include->creator = true;
	$query->filter->creatorId = [5];
	$query->limit = 2;
	$query->sort="random";
	$assetsToActivate = loadAssetsFromDatabase($query);
	foreach ($assetsToActivate->assets as $a) {
		$creatorId = $a->creator->creatorId;
		$creatorClass = "Creator".$creatorId;

		$imageData = fetchRemoteData($a->thumbnailUrl);

		require_once $_SERVER['DOCUMENT_ROOT']."/creators/$creatorId/main.php";
		$creator = new $creatorClass();
		$imageData = $creator->postProcessThumbnail($imageData);

		buildAndUploadThumbnailsToBackblazeB2($a->assetId,$imageData);
	}
	activateAssetCollection($assetsToActivate);
	echoCurrentLog();
?>