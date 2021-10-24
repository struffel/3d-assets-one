<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/backblaze.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/json.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	$query = new AssetQuery();

	foreach (explode(",",$_GET['include']??"") as $i) {
		if(isset($query->include->$i)){
			$query->include->$i = true;
		}
	}

	$query->filter->assetId = array_filter(explode(",",$_GET['asset']??""));
	$query->filter->tag = array_filter(explode(",",$_GET['tags']??""));
	$query->filter->licenseSlug = array_filter(explode(",",$_GET['license']??""));
	$query->filter->typeSlug = array_filter(explode(",",$_GET['type']??""));
	$query->filter->creatorSlug = array_filter(explode(",",$_GET['creator']??""));
	$query->limit = intval($_GET['limit']??"100");
	$query->offset = intval($_GET['offset']??"0");

	$output = loadAssetsFromDatabase($query);
	outputJson($output);
?>