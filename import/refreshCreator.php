<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/strings.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/json.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';


	$creatorId = onlyNumbers($_GET['creatorId']);
	initializeLog("refreshCreator-".$creatorId);
	require_once $_SERVER['DOCUMENT_ROOT']."/creators/$creatorId/main.php";

	$creatorClass = "Creator".$creatorId;
	$creator = new $creatorClass();
	createLog("Created creator object.");
	$result = $creator->findNewAssets();
	createLog("Found ".sizeof($result->assets)." new assets");
	if(sizeof($result->assets) > 0){
		createLog("Writing new assets to DB:");
		writeAssetCollectionToDatabase($result);
		createLog("Wrote new assets.");
	}
	echoCurrentLog();
?>