<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/backblaze.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/images.php';
	initializeLog("playgroundBackblazeTest");

	initializeBackblazeB2();
	$img = fetchImageFromUrl("https://cdn3.struffelproductions.com/file/ambientCG/media/sphere/512-PNG/PavingStones108_PREVIEW.png");
	$thumbnails = createThumbnailVariations($img,"test");
	uploadThumbnailsToBackblazeB2($thumbnails);
	echo testForThumbnailsOnBackblazeB2ByAssetId("test");
	echoCurrentLog();
?>