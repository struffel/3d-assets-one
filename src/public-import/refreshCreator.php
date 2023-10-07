<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';


	$refreshConfig = parse_ini_file("importConfig.ini",true);
	if(isset($_GET['creatorId'])){
		$creatorId = onlyNumbers($_GET['creatorId']);
	}else{
		$randomTargets = explode(",",$refreshConfig['refreshCreator']['randomTargets']);
		$randomIndex = array_rand($randomTargets);
		$creatorId = $randomTargets[$randomIndex];
	}
		
	
	$maxNumberOfAssets = intval($_GET['max']??1);
	initializeLog("refreshCreator-".$creatorId);
	LogLogic::write("Refreshing Creator: $creatorId");
	require_once $_SERVER['DOCUMENT_ROOT']."/../creators/$creatorId/main.php";

	$creatorClass = "Creator".$creatorId;
	$creator = new $creatorClass();
	LogLogic::write("Created creator object.");
	$result = $creator->findNewAssets();
	LogLogic::write("Found ".sizeof($result->assets)." new assets");
	if(sizeof($result->assets) > 0){
		LogLogic::write("Writing new assets to DB:");
		writeAssetCollectionToDatabase($result);
		LogLogic::write("Wrote ".sizeof($result->assets)." new assets.");
	}
	echoCurrentLog();
?>