<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';

    $assetId = intval($_GET['id']??"0");

    $url = loadUrlFromDatabase($assetId);
    if($url){
        header("Location: $url");
    }else{
        die("3Dassets.one\nURL could not be resolved.");
    }
    countAssetClick($assetId);
?>