<?php

	function getThumbnailTemplate(){
		$output = [];
		$output []=	["JPG","FFFFFF",32];
		$output []=	["JPG","FFFFFF",64];
		$output []=	["JPG","FFFFFF",128];
		$output []=	["JPG","FFFFFF",256];
		$output []=	["PNG",NULL,32];
		$output []=	["PNG",NULL,64];
		$output []=	["PNG",NULL,128];
		$output []=	["PNG",NULL,256];
		return $output;
	}

	function getBackblazeB2ThumbnailPath($size,$extension,$backgroundColor,$assetId){
		$variation = strtoupper(implode("-",array_filter([$size,$extension,$backgroundColor])));
		$extension = strtolower($extension);
		return "thumbnail/$variation/$assetId.$extension";
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

	function buildAndUploadThumbnailsToBackblazeB2($assetId,$originalImageData){
		changeLogIndentation(true,__FUNCTION__);
		foreach (getThumbnailTemplate() as $t) {
			$tmpThumbnail = createThumbnailFromImageData($originalImageData,$t[2],$t[0],$t[1],$assetId);
			uploadDataToBackblazeB2($tmpThumbnail,getBackblazeB2ThumbnailPath($t[2],$t[0],$t[1],$assetId));
		}
		changeLogIndentation(false,__FUNCTION__);
	}

	function parseImageIntoPng(string $imageBlob):string{
		// Read image using GD to ensure webP-compatibility and proper alpha handling
		$tmpImage = imagecreatefromstring($imageBlob);
		imagealphablending($tmpImage, false);
		imagesavealpha($tmpImage, true);
		$stream = fopen('php://memory','r+');
		imagepng($tmpImage,$stream);
		rewind($stream);
		return stream_get_contents($stream);

	}

	function createThumbnailFromImageData($originalImageData,$size,$extension,$backgroundColor,$assetId){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Building variation: $size/$extension/$backgroundColor/$assetId ");
		$originalImageData = parseImageIntoPng($originalImageData);

		// Read image using Imagick for further processing
		$tmpImage = new Imagick();
		$tmpImage->readImageBlob($originalImageData);
		$tmpImage->setbackgroundcolor('transparent');
		$tmpImage->setGravity(imagick::GRAVITY_CENTER);
		$tmpImage->setImageAlphaChannel(imagick::ALPHACHANNEL_ACTIVATE);

		$tmpImage->thumbnailImage($size,$size,true);
		$offsetX = ($size-$tmpImage->getImageWidth())/2 ;
		$offsetY = ($size-$tmpImage->getImageHeight())/2;
		
		$outputImage = new Imagick();
		$outputImage->newImage($size,$size,'transparent','png');
		$outputImage->compositeImage($tmpImage, imagick::COMPOSITE_DEFAULT, $offsetX, $offsetY);

		$outputImage->setImageFormat(strtolower($extension));

		if($backgroundColor ?? "" != ""){
			$outputImage->setbackgroundcolor('#'.$backgroundColor);
			$outputImage = $outputImage->flattenImages();
		}
		
		changeLogIndentation(false,__FUNCTION__);
		return $outputImage->getImageBlob();
	}

	function removeUniformBackground($imageBlob,$targetX,$targetY,$fuzz):string{
		$imageBlob = parseImageIntoPng($imageBlob);
		$tmpImage = new Imagick();
		$tmpImage->readImageBlob($imageBlob);
		$targetColor = $tmpImage->getImagePixelColor($targetX,$targetY);
		$tmpImage->transparentPaintImage($targetColor,0,Imagick::getQuantum() * $fuzz,false);
		return $tmpImage->getImageBlob();
	}

?>