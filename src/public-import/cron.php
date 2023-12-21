<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

LogLogic::initialize("cron");

$allActions = !isset($_GET['action']);
$action = $_GET['action'] ?? NULL;

try{
	
	// Activate one or multiple assets

	if($allActions | $action == "activate" ){

		$maxNumberOfAssets = max(1,intval($_GET['number'] ?? 0));

		$query = new AssetQuery(
			filterStatus:  ASSET_STATUS::INACTIVE,
			limit: $maxNumberOfAssets,
			sort: SORTING::RANDOM,
		);
	
		$assetsToActivate = AssetLogic::getAssets($query);
		foreach ($assetsToActivate->assets as $a) {
	
			DatabaseLogic::startTransaction();

			LogLogic::write("Getting thumbnail for asset ". $a->id." from url ".$a->thumbnailUrl);
	
			$creatorFetcher = CreatorFetcher::fromCreator($a->creator);
			$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);
	
			ImageLogic::buildAndUploadThumbnailsToBackblazeB2($a,$imageData);
			$a->status = ASSET_STATUS::ACTIVE;
			AssetLogic::saveAssetToDatabase($a);
	
			DatabaseLogic::commitTransaction();
		}
	}

	// Refresh one creator

	if($allActions | $action == "refresh"){

		if(isset($_GET['creatorId'])){
			$creator = CREATOR::from(intval($_GET['creatorId']));
		}else{
			$randomTargets = CREATOR::regularRefreshList();
			$randomIndex = array_rand($randomTargets);
			$creator = $randomTargets[$randomIndex];
		}

		$maxNumberOfAssets = intval($_GET['max']??1);
		LogLogic::write("Refreshing Creator: ".$creator->slug());

		$creator = CreatorFetcher::fromCreator($creator);
		LogLogic::write("Created creator object.");
		$result = $creator->runUpdate();

		LogLogic::write("Found ".sizeof($result->assets)." new assets");
		if(sizeof($result->assets) > 0){
			LogLogic::write("Writing new assets to DB:");
			foreach ($result->assets as $a) {
				DatabaseLogic::startTransaction();
				$a->status = ASSET_STATUS::INACTIVE;	// Failsave in case the creator fetching function does not set it properly.
				AssetLogic::saveAssetToDatabase($a);
				DatabaseLogic::commitTransaction();
			}
			LogLogic::write("Wrote ".sizeof($result->assets)." new assets.");
		}
	}

	if( $allActions | $action == "livecheck"){
		// Coming soon
	}
	
}finally{
	LogLogic::echoCurrentLog();
}