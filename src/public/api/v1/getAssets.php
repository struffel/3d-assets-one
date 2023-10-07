<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/../functions/init.php';

	$query = new AssetQuery();

	foreach (explode(",",$_GET['include']??"") as $i) {
		if(isset($query->include->$i)){
			$query->include->$i = true;
		}
	}

	$query->filter->assetId = array_filter(explode(",",$_GET['asset']??""));
	if(str_contains($_GET['tags']??"",",")){
		$query->filter->tag = array_filter(explode(",",$_GET['tags']??""));
	}else{
		$query->filter->tag = array_filter(explode(" ",$_GET['tags']??""));
	}
		

	$query->filter->licenseSlug = array_filter(explode(",",$_GET['license']??""));
	$query->filter->typeSlug = array_filter(explode(",",$_GET['type']??""));
	$query->filter->creatorSlug = array_filter(explode(",",$_GET['creator']??""));
	$query->sort = $_GET['sort']??"";
	$query->limit = intval($_GET['limit']??"100");
	$query->offset = intval($_GET['offset']??"0");

	$output = DatabaseLogic::getAssets($query);
	JsonLogic::output($output);
?>