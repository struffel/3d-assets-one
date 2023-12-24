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
			filterStatus:  ASSET_STATUS::PENDING,
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

		$creatorFetcher = CreatorFetcher::fromCreator($creator);
		LogLogic::write("Created creator object.");
		$result = $creatorFetcher->runUpdate();

		LogLogic::write("Found ".sizeof($result->assets)." new assets");
		if(sizeof($result->assets) > 0){
			LogLogic::write("Writing new assets to DB:");
			foreach ($result->assets as $a) {
				DatabaseLogic::startTransaction();
				$a->status = ASSET_STATUS::PENDING;	// Failsave in case the creator fetching function does not set it properly.
				AssetLogic::saveAssetToDatabase($a);
				DatabaseLogic::commitTransaction();
			}
			LogLogic::write("Wrote ".sizeof($result->assets)." new assets.");
		}
	}


	// Validate assets

	if( $allActions | $action == "validate"){

		// Get at least 2 assets to validate, 6 by default
		$maxNumberOfAssets = max(2,intval($_GET['number'] ?? 4));
		$assetsToCheck = [];

		// Select creator
		if(isset($_GET['creatorId'])){
			$filterCreator = [CREATOR::from(intval($_GET['creatorId']))];
		}else{
			$filterCreator = [];
		}
		
		// Get active assets to check
		$assetsToCheck = array_merge($assetsToCheck, AssetLogic::getAssets(new AssetQuery(
			limit: $maxNumberOfAssets/2,
			filterStatus: ASSET_STATUS::ACTIVE,
			sort: SORTING::OLDEST_VALIDATION_SUCCESS,
			filterCreator: $filterCreator
		))->assets);

		// Get assets that failed their validation
		$assetsToCheck = array_merge($assetsToCheck, AssetLogic::getAssets(new AssetQuery(
			limit: $maxNumberOfAssets/2,
			filterStatus: ASSET_STATUS::VALIDATION_FAILED_RECENTLY,
			sort: SORTING::RANDOM,
			filterCreator: $filterCreator
		))->assets);
		
		foreach ($assetsToCheck as $asset) {

			DatabaseLogic::startTransaction();

			LogLogic::write("Testing asset ".$asset->id);
			LogLogic::write("Asset made by ".$asset->creator->slug());

			$creatorFetcher = CreatorFetcher::fromCreator($asset->creator);
			$currentDateTime = new DateTime();

			// Test if the asset is still valid
			if($creatorFetcher->validateAsset($asset)){
				$asset->lastSuccessfulValidation = $currentDateTime;
				$asset->status = ASSET_STATUS::ACTIVE;
				LogLogic::write("Validation OK");
			}else{

				// If the asset is invalid and was already invalid before the test, check if its last successful validation was 2 or more days ago.
				// In that case it is considered failed permanently and will not be added to the validation rotation again.
				if($asset->status == ASSET_STATUS::ACTIVE | $currentDateTime->diff($asset->lastSuccessfulValidation)->d < 2){
					$asset->status = ASSET_STATUS::VALIDATION_FAILED_RECENTLY;
					LogLogic::write("Validation Failed (Recently)","WARN");
				}else{
					$asset->status = ASSET_STATUS::VALIDATION_FAILED_PERMANENTLY;
					LogLogic::write("Validation Failed (Permanently)","WARN");
				}
					
			}
			AssetLogic::saveAssetToDatabase($asset);

			DatabaseLogic::commitTransaction();

		}
	}
	
}finally{
	LogLogic::echoCurrentLog();
}