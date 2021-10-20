<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/strings.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/json.php';

	$creatorSlug = onlySmallLetters($_GET['creator']);
	require_once $_SERVER['DOCUMENT_ROOT']."/creators/$creatorSlug/main.php";

	$creatorClass = ucfirst($creatorSlug);
	$creator = new $creatorClass();
	outputJson($creator->findNewAssets());
?>