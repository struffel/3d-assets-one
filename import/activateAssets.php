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
	$query->limit = 2;
	$query->sort="random";
	$assetsToActivate = loadAssetsFromDatabase($query);
	fetchAndUploadThumbnailsToBackblazeB2ForAssetCollection($assetsToActivate);
	activateAssetCollection($assetsToActivate);
	echoCurrentLog();
?>