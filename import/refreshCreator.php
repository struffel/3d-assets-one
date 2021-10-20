<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/strings.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/json.php';

	$creatorId = onlyNumbers($_GET['creatorId']);
	require_once $_SERVER['DOCUMENT_ROOT']."/creators/$creatorId/main.php";

	$creatorClass = "Creator".$creatorId;
	$creator = new $creatorClass();
	outputJson($creator->findNewAssets());
?>