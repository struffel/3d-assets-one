<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/backblaze.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';

	function getThumbnailVariations(){
		$variations = [
			"128-JPG-FFFFFF",
			"128-PNG",
			"256-JPG-FFFFFF",
			"256-PNG",
			"512-JPG-FFFFFF",
			"512-PNG"
		];
		return $variations;
	}

	function testForThumbnailsOnBackblazeB2ByAssetId($assetId){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Testing thumbnails for $assetId");

		
		$isPresent = true;

		foreach (getThumbnailVariations() as $v) {
			$imageProperties = explode("-",$v);
			$isPresent &= testForFileOnBackblazeB2("thumbnail/$v/$assetId.".strtolower($imageProperties[1]));
		}
		createLog("Result: ".$isPresent);
		changeLogIndentation(false,__FUNCTION__);
		return $isPresent;
	}

	function uploadThumbnailsToBackblazeB2($thumbnails){
		changeLogIndentation(true,__FUNCTION__);
		foreach ($thumbnails as $t) {
			uploadDataToBackblazeB2($t->imageData,"thumbnail/".$t->variation."/".$t->assetId.".".$t->extension);
		}
		changeLogIndentation(false,__FUNCTION__);
	}

	function createThumbnailVariations($imageData,$assetId){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Creating variations for: ".$assetId);

		$thumbnails = [];

		foreach (getThumbnailVariations() as $v) {

			$imageProperties = explode("-",$v);
			createLog("Building variation: ".var_export($imageProperties,true));
			$tmpImage = new Imagick();
			$tmpImage->readImageBlob($imageData);

			if($imageProperties[1] == "JPG"){
				$tmpImage->setbackgroundcolor('#'.$imageProperties[2]);
				$tmpImage = $tmpImage->flattenImages();
				$tmpImage->setImageFormat('jpg');
			}
			
			$tmpImage->thumbnailImage($imageProperties[0],0);
			$tmpThumbnail = new Thumbnail();
			$tmpThumbnail->imageData = $tmpImage->getImageBlob();
			$tmpThumbnail->variation = $v;
			$tmpThumbnail->assetId = $assetId;
			$tmpThumbnail->extension = strtolower($imageProperties[1]);

			$thumbnails []= $tmpThumbnail;
		}
		changeLogIndentation(false,__FUNCTION__);
		return $thumbnails;
	}

	function fetchImageFromUrl($url){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Fetching: ".$url);
		$image = file_get_contents($url);
		changeLogIndentation(false,__FUNCTION__);
		return $image;
	}

?>