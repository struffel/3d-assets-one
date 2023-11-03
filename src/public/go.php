<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

    $assetId = intval($_GET['id']??"0");

    $url = AssetLogic::getUrlFromAssetId($assetId);
    if($url){
        header("Location: $url");
    }else{
        die("3Dassets.one\nURL could not be resolved.");
    }
    AssetLogic::addAssetClickByAssetId($assetId);
?>