<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	LogLogic::initialize("activateAssets");

	try{
		DatabaseLogic::startTransaction();
		$query = new AssetQuery(
			filterStatus:  ASSET_STATUS::INACTIVE,
			limit: 2,
			sort: SORTING::RANDOM,
		);

		$assetsToActivate = AssetIoLogic::getAssets($query);
		foreach ($assetsToActivate->assets as $a) {

			$creatorFetcher = CreatorFetcher::fromCreator($a->creator);
			$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);

			ImageLogic::buildAndUploadThumbnailsToBackblazeB2($a,$imageData);
		}
		AssetIoLogic::activateAssetCollection($assetsToActivate);
		DatabaseLogic::commitTransaction();
	}finally{
		LogLogic::echoCurrentLog();
	}
	
?>