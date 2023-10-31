<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	$refreshConfig = json_decode("importConfig.json",true);
	if(isset($_GET['creatorId'])){
		$creatorId = StringLogic::onlyNumbers($_GET['creatorId']);
	}else{
		$randomTargets = $refreshConfig['refreshCreator']['randomTargets'];
		$randomIndex = array_rand($randomTargets);
		$creatorId = $randomTargets[$randomIndex];
	}
	
	$maxNumberOfAssets = intval($_GET['max']??1);
	LogLogic::initialize("refreshCreator-".$creatorId);
	LogLogic::write("Refreshing Creator: $creatorId");
	
	$creator = CreatorFetcher::fromCreator(CREATOR::from($creatorId));
	LogLogic::write("Created creator object.");
	$result = $creator->runUpdate();

	LogLogic::write("Found ".sizeof($result->assets)." new assets");
	if(sizeof($result->assets) > 0){
		LogLogic::write("Writing new assets to DB:");
		AssetLogic::writeAssetCollectionToDatabase($result);
		LogLogic::write("Wrote ".sizeof($result->assets)." new assets.");
	}
	LogLogic::echoCurrentLog();
?>