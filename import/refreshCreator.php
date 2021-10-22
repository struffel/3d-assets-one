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
	$result = $creator->findNewAssets();
	writeAssetCollectionToDatabase($result);
	outputJson($result);
	createLog("--- End","END")
?>