<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	LogLogic::initialize("activateAssets");

	$query = new AssetQuery(
		filterActive: false,
		#includeInternal?
		limit: 2,
		sort: SortingOrder::RANDOM,

	);

	$assetsToActivate = AssetLogic::getAssets($query);
	foreach ($assetsToActivate->assets as $a) {

		$creatorFetcher = CreatorFetcher::fromCreator($a->creator);
		$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);

		ImageLogic::buildAndUploadThumbnailsToBackblazeB2($a,$imageData);
	}
	AssetLogic::activateAssetCollection($assetsToActivate);
	LogLogic::echoCurrentLog();
?>