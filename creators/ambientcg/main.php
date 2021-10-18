<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/classes.php';

	class Ambientcg extends Creator{
		 function findNewAssets():AssetCollection{
			$tmpAsset = new Asset();
			$tmpAsset->creatorId = "abc";
			$tmpAsset->assetId = "def";
			$tmpCollection = new AssetCollection();
			$tmpCollection->assets[] = $tmpAsset;
			$tmpCollection->numberOfResults = 1;
			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return "bar";
		}
	}
?>