<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/../functions/init.php';

    $assetId = intval($_GET['id']??"0");

    $url = DatabaseLogic::getUrlFromAssetId($assetId);
    if($url){
        header("Location: $url");
    }else{
        die("3Dassets.one\nURL could not be resolved.");
    }
    DatabaseLogic::addAssetClickByAssetId($assetId);
?>