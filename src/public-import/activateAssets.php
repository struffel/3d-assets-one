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

		$assetsToActivate = AssetLogic::getAssets($query);
		foreach ($assetsToActivate->assets as $a) {

			$creatorFetcher = CreatorFetcher::fromCreator($a->creator);
			$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);

			ImageLogic::buildAndUploadThumbnailsToBackblazeB2($a,$imageData);
			$a->status = ASSET_STATUS::ACTIVE;
			$a->saveToDatabase();
		}
		DatabaseLogic::commitTransaction();
	}finally{
		LogLogic::echoCurrentLog();
	}
	
?>