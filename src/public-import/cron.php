<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

Log::initialize("cron");

if($_GET['action'] ?? false){
	$action = CRON_ACTION::from($_GET['action']);
}else{
	$action = CRON_ACTION::cases()[random_int(0,2)];
}

try{
	
	// Activate one or multiple assets

	if($action == CRON_ACTION::ACTIVATE ){

		$maxNumberOfAssets = max(1,intval($_GET['number'] ?? 0));

		if(isset($_GET['creatorId'])){
			$creators = [CREATOR::from(intval($_GET['creatorId']))];
		}else{
			$creators = [];
		}

		$query = new AssetQuery(
			filterStatus:  AssetStatus::PENDING,
			limit: $maxNumberOfAssets,
			sort: SORTING::RANDOM,
			filterCreator:$creators ?? NULL
		);
	
		$assetsToActivate = AssetLogic::getAssets($query);
		foreach ($assetsToActivate->assets as $a) {
	
			DatabaseLogic::startTransaction();

			Log::write("Getting thumbnail for asset ". $a->id." from url ".$a->thumbnailUrl);
	
			$creatorFetcher = CreatorFetcher::fromCreator($a->creator);
			$imageData = $creatorFetcher->fetchThumbnailImage($a->thumbnailUrl);
	
			ImageLogic::buildAndUploadThumbnailsToBackblazeB2($a,$imageData);
			$a->status = AssetStatus::ACTIVE;
			AssetLogic::saveAssetToDatabase($a);
	
			DatabaseLogic::commitTransaction();
		}
	}

	// Refresh one creator

	if( $action == CRON_ACTION::REFRESH){

		if(isset($_GET['creatorId'])){
			$creator = CREATOR::from(intval($_GET['creatorId']));
		}else{
			$randomTargets = CREATOR::regularRefreshList();
			$randomIndex = array_rand($randomTargets);
			$creator = $randomTargets[$randomIndex];
		}

		$maxNumberOfAssets = intval($_GET['max']??1);
		Log::write("Refreshing Creator: ".$creator->slug());

		$creatorFetcher = CreatorFetcher::fromCreator($creator);
		Log::write("Created creator object.");
		$result = $creatorFetcher->runUpdate();

		Log::write("Found ".sizeof($result->assets)." new assets");
		if(sizeof($result->assets) > 0){
			Log::write("Writing new assets to DB:");
			foreach ($result->assets as $a) {
				DatabaseLogic::startTransaction();
				$a->status = AssetStatus::PENDING;	// Failsave in case the creator fetching function does not set it properly.
				AssetLogic::saveAssetToDatabase($a);
				DatabaseLogic::commitTransaction();
			}
			Log::write("Wrote ".sizeof($result->assets)." new assets.");
		}
	}


	// Validate assets

	if($action == CRON_ACTION::VALIDATE){

		// Get at least 2 assets to validate, 6 by default
		$maxNumberOfAssets = max(2,intval($_GET['number'] ?? 6));
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
			filterStatus: AssetStatus::ACTIVE,
			sort: SORTING::OLDEST_VALIDATION_SUCCESS,
			filterCreator: $filterCreator
		))->assets);

		// Get assets that failed their validation
		$assetsToCheck = array_merge($assetsToCheck, AssetLogic::getAssets(new AssetQuery(
			limit: $maxNumberOfAssets/2,
			filterStatus: AssetStatus::VALIDATION_FAILED_RECENTLY,
			sort: SORTING::RANDOM,
			filterCreator: $filterCreator
		))->assets);
		
		foreach ($assetsToCheck as $asset) {

			DatabaseLogic::startTransaction();

			Log::write("Testing asset ".$asset->id);
			Log::write("Asset made by ".$asset->creator->slug());

			$creatorFetcher = CreatorFetcher::fromCreator($asset->creator);
			$currentDateTime = new DateTime();

			// Test if the asset is still valid
			try {
				$testResult = $creatorFetcher->validateAsset($asset);
			} catch (\Throwable $th) {
				Log::write("Skipping this asset because validation function threw exception.","ERROR");
				continue;
			}

			if($testResult){
				$asset->lastSuccessfulValidation = $currentDateTime;
				$asset->status = AssetStatus::ACTIVE;
				Log::write("Validation OK");
			}else{

				// If the asset is invalid and was already invalid before the test, check if its last successful validation was 2 or more days ago.
				// In that case it is considered failed permanently and will not be added to the validation rotation again.
				if($asset->status == AssetStatus::ACTIVE | $currentDateTime->diff($asset->lastSuccessfulValidation)->d < 2){
					$asset->status = AssetStatus::VALIDATION_FAILED_RECENTLY;
					Log::write("Validation Failed (Recently)","WARN");
				}else{
					$asset->status = AssetStatus::VALIDATION_FAILED_PERMANENTLY;
					Log::write("Validation Failed (Permanently)","WARN");
				}
					
			}
			AssetLogic::saveAssetToDatabase($asset);

			DatabaseLogic::commitTransaction();

		}
	}
	
}finally{
	Log::echoCurrentLog();
}