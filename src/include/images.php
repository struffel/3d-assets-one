<?php

class ImageLogic{
	private static array $thumbnailTemplate = [
		["JPG","FFFFFF",32],
		["JPG","FFFFFF",64],
		["JPG","FFFFFF",128],
		["JPG","FFFFFF",256],
		["PNG",NULL,32],
		["PNG",NULL,64],
		["PNG",NULL,128],
		["PNG",NULL,256]
	];

	public static function getBackblazeB2ThumbnailPath(?int $size,?string $extension,?string $backgroundColor,?string $assetId){
		$variation = strtoupper(implode("-",array_filter([$size,$extension,$backgroundColor])));
		$extension = strtolower($extension);
		return "thumbnail/$variation/$assetId.$extension";
	}

	public static function testForThumbnailsOnBackblazeB2(Asset $asset) : bool{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Testing thumbnails for ".$asset->assetId);
		
		$isPresent = true;

		foreach (ImageLogic::$thumbnailTemplate as $t) {
			$isPresent &= BackblazeB2Logic::testForFile(ImageLogic::getBackblazeB2ThumbnailPath($t[2],$t[0],$t[1],$asset->assetId));
		}
		
		LogLogic::write("Result: ".$isPresent);
		LogLogic::stepOut(__FUNCTION__);
		return $isPresent;
	}

	public static function buildAndUploadThumbnailsToBackblazeB2(string $assetId,string $originalImageData){
		LogLogic::stepIn(__FUNCTION__);
		foreach (ImageLogic::$thumbnailTemplate as $t) {
			$tmpThumbnail = ImageLogic::createThumbnailFromImageData($originalImageData,$t[2],$t[0],$t[1],$assetId);
			BackblazeB2Logic::uploadData($tmpThumbnail,ImageLogic::getBackblazeB2ThumbnailPath($t[2],$t[0],$t[1],$assetId));
		}
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function parseImageIntoPng(string $imageBlob):string{
		// Read image using GD to ensure webP-compatibility and proper alpha handling
		$tmpImage = imagecreatefromstring($imageBlob);
		imagealphablending($tmpImage, false);
		imagesavealpha($tmpImage, true);
		$stream = fopen('php://memory','r+');
		imagepng($tmpImage,$stream);
		rewind($stream);
		return stream_get_contents($stream);

	}

	public static function createThumbnailFromImageData(string $originalImageData,int $size,string $extension,string $backgroundColor,string $assetId) : string{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Building variation: $size/$extension/$backgroundColor/$assetId ");
		$originalImageData = ImageLogic::parseImageIntoPng($originalImageData);

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
		
		LogLogic::stepOut(__FUNCTION__);
		return $outputImage->getImageBlob();
	}

	public static function removeUniformBackground(string $imageBlob,int $targetX,int $targetY,int $fuzz):string{
		$imageBlob = ImageLogic::parseImageIntoPng($imageBlob);
		$tmpImage = new Imagick();
		$tmpImage->readImageBlob($imageBlob);
		$targetColor = $tmpImage->getImagePixelColor($targetX,$targetY);
		$tmpImage->transparentPaintImage($targetColor,0,Imagick::getQuantum() * $fuzz,false);
		return $tmpImage->getImageBlob();
	}

}
?>