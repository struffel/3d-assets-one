<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	LogLogic::initialize("activateAssets");

	try{
		$query = new AssetQuery(
			filterStatus:  ASSET_STATUS::INACTIVE,
			limit: 2,
			sort: SORTING::RANDOM,
		);

		$assetsToActivate = AssetLogic::getAssets($query);
		foreach ($assetsToActivate->assets as $a) {

			$creatorFetcher = CreatorFetcher::fromCreator($a->creator);
			$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);

			ImageLogic::buildAndUploadThumbnailsToBackblazeB2($a,$imageData);
		}
		AssetLogic::activateAssetCollection($assetsToActivate);
	}finally{
		LogLogic::echoCurrentLog();
	}
	
?>