<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/backblaze.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';

	function getThumbnailTemplate(){
		$output = [];
		$output []=	["JPG","FFFFFF",128];
		$output []=	["JPG","FFFFFF",256];
		$output []=	["JPG","FFFFFF",512];
		$output []=	["PNG",NULL,128];
		$output []=	["PNG",NULL,256];
		$output []=	["PNG",NULL,512];
		return $output;
	}

	function getBackblazeB2ThumbnailPath($size,$extension,$backgroundColor,$assetId){
		$variation = strtoupper(implode("-",array_filter([$size,$extension,$backgroundColor])));
		$extension = strtolower($extension);
		return "thumbnails/$variation/$assetId.$extension";
	}

	function testForThumbnailsOnBackblazeB2(Asset $asset) : bool{
		changeLogIndentation(true,__FUNCTION__);
		createLog("Testing thumbnails for ".$asset->assetId);
		
		$isPresent = true;

		foreach (getThumbnailTemplate() as $t) {
			$isPresent &= testForFileOnBackblazeB2(getBackblazeB2ThumbnailPath($t[2],$t[0],$t[1],$asset->assetId));
		}
		createLog("Result: ".$isPresent);
		changeLogIndentation(false,__FUNCTION__);
		return $isPresent;
	}

	function fetchAndUploadThumbnailsToBackblazeB2ForAssetCollection(AssetCollection $assetCollection){
		foreach ($assetCollection->assets as $a) {
			fetchAndUploadThumbnailsToBackblazeB2($a);
		}
	}

	function fetchAndUploadThumbnailsToBackblazeB2(Asset $asset){
		changeLogIndentation(true,__FUNCTION__);
		$originalImageData = fetchImageFromUrl($asset->thumbnailUrl);
		foreach (getThumbnailTemplate() as $t) {
			$tmpThumbnail = createThumbnailFromImageData($originalImageData,$t[2],$t[0],$t[1],$asset->assetId);
			uploadDataToBackblazeB2($tmpThumbnail,getBackblazeB2ThumbnailPath($t[2],$t[0],$t[1],$asset->assetId));
		}
		changeLogIndentation(false,__FUNCTION__);
	}

	function createThumbnailFromImageData($originalImageData,$size,$extension,$backgroundColor,$assetId){
		changeLogIndentation(true,__FUNCTION__);

		createLog("Building variation: $size/$extension/$backgroundColor/$assetId ");
		$tmpImage = new Imagick();
		$tmpImage->readImageBlob($originalImageData);

		if(strtolower($extension) == "jpg"){
			$tmpImage->setbackgroundcolor('#'.$backgroundColor);
			$tmpImage = $tmpImage->flattenImages();
			$tmpImage->setImageFormat('jpg');
		}
		
		$tmpImage->thumbnailImage($size,0);
		
		changeLogIndentation(false,__FUNCTION__);
		return $tmpImage->getImageBlob();
	}

	function fetchImageFromUrl($url){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Fetching: ".$url);
		$image = file_get_contents($url);
		changeLogIndentation(false,__FUNCTION__);
		return $image;
	}

?>