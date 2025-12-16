<?php

namespace misc;

use asset\Asset;
use misc\BackblazeB2;
use log\Log;

use Imagick;

class Image
{
	private static array $thumbnailTemplate = [
		["JPG", "FFFFFF", 32],
		["JPG", "FFFFFF", 64],
		["JPG", "FFFFFF", 128],
		["JPG", "FFFFFF", 256],
		["PNG", NULL, 32],
		["PNG", NULL, 64],
		["PNG", NULL, 128],
		["PNG", NULL, 256]
	];

	public static function getBackblazeB2ThumbnailPath(int $size, string $extension, ?string $backgroundColor, Asset $asset)
	{
		$variation = strtoupper(implode("-", array_filter([$size, $extension, $backgroundColor])));
		$extension = strtolower($extension);
		$id = $asset->id;
		return "thumbnail/$variation/$id.$extension";
	}


	public static function buildAndUploadThumbnailsToBackblazeB2(Asset $asset, string $originalImageData)
	{

		foreach (Image::$thumbnailTemplate as $t) {
			$tmpThumbnail = Image::createThumbnailFromImageData($originalImageData, $t[2], $t[0], $t[1] ?? "");
			BackblazeB2::uploadData($tmpThumbnail, Image::getBackblazeB2ThumbnailPath($t[2], $t[0], $t[1], $asset));
		}
	}

	public static function parseImageIntoPng(string $imageBlob): string
	{
		// Read image using GD to ensure webP-compatibility and proper alpha handling
		$tmpImage = imagecreatefromstring($imageBlob);
		imagealphablending($tmpImage, false);
		imagesavealpha($tmpImage, true);
		$stream = fopen('php://memory', 'r+');
		imagepng($tmpImage, $stream);
		rewind($stream);
		return stream_get_contents($stream);
	}

	public static function createThumbnailFromImageData(string $originalImageData, int $size, string $extension, string $backgroundColor): string
	{

		Log::write("Building variation: $size/$extension/$backgroundColor ");
		$originalImageData = self::parseImageIntoPng($originalImageData);

		// Read image using Imagick for further processing
		$tmpImage = new Imagick();
		$tmpImage->readImageBlob($originalImageData);
		$tmpImage->setbackgroundcolor('transparent');
		$tmpImage->setGravity(Imagick::GRAVITY_CENTER);
		$tmpImage->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

		$tmpImage->thumbnailImage($size, $size, true);
		$offsetX = ($size - $tmpImage->getImageWidth()) / 2;
		$offsetY = ($size - $tmpImage->getImageHeight()) / 2;

		$outputImage = new Imagick();
		$outputImage->newImage($size, $size, 'transparent', 'png');
		$outputImage->compositeImage($tmpImage, Imagick::COMPOSITE_DEFAULT, $offsetX, $offsetY);

		$outputImage->setImageFormat(strtolower($extension));

		if ($backgroundColor ?? "" != "") {
			$outputImage->setbackgroundcolor('#' . $backgroundColor);
			$outputImage = $outputImage->flattenImages();
		}


		return $outputImage->getImageBlob();
	}

	public static function removeUniformBackground(string $imageBlob, int $targetX, int $targetY, int $fuzz): string
	{
		$imageBlob = Image::parseImageIntoPng($imageBlob);
		$tmpImage = new Imagick();
		$tmpImage->readImageBlob($imageBlob);
		$targetColor = $tmpImage->getImagePixelColor($targetX, $targetY);
		$tmpImage->transparentPaintImage($targetColor, 0, Imagick::getQuantum() * $fuzz, false);
		return $tmpImage->getImageBlob();
	}
}
