<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../functions/init.php';

	initializeLog("activateAssets");

	$query = new AssetQuery();
	$query->filter->active=false;
	$query->include->internal=true;
	$query->include->creator = true;
	$query->limit = 2;
	$query->sort="random";
	$assetsToActivate = loadAssetsFromDatabase($query);
	foreach ($assetsToActivate->assets as $a) {

		$creatorId = $a->creator->creatorId;
		$creatorClass = "Creator".$creatorId;
		require_once $_SERVER['DOCUMENT_ROOT']."/../creators/$creatorId/main.php";
		$creator = new $creatorClass();

		$imageData = fetchRemoteData($a->thumbnailUrl,$creator->generateThumbnailFetchingHeaders());
		$imageData = $creator->postProcessThumbnail($imageData);

		buildAndUploadThumbnailsToBackblazeB2($a->assetId,$imageData);
	}
	activateAssetCollection($assetsToActivate);
	echoCurrentLog();
?>