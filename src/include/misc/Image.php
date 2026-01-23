<?php

namespace misc;

use asset\Asset;
use database\Database;
use log\Log;

use GdImage;

class Image
{

	private static string $thumbnailDirectory =  __DIR__ . "/../../public/img/thumbnail/";

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

	/**
	 * Deletes all thumbnail variations carrying asset ids no longer in the database.
	 * @return void 
	 */
	public static function deleteOrphanedThumbnails()
	{
		$existingIds = [];
		$dbResult = Database::runQuery("SELECT id FROM Asset");
		while ($row = $dbResult->fetchArray()) {
			$existingIds[] = $row['id'];
		}
		$thumbnailDir = self::$thumbnailDirectory;
		foreach (scandir($thumbnailDir) as $variationDir) {
			if ($variationDir === '.' || $variationDir === '..') {
				continue;
			}
			$fullVariationDir = $thumbnailDir . $variationDir . "/";
			foreach (scandir($fullVariationDir) as $file) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				$assetId = intval(pathinfo($file, PATHINFO_FILENAME));
				if (!in_array($assetId, $existingIds)) {
					unlink($fullVariationDir . $file);
					Log::write("Deleted orphaned thumbnail", ["assetId" => $assetId, "fileName" => $fullVariationDir . $file]);
				}
			}
		}
	}

	public static function saveThumbnailVariations(int $assetId, string $originalImageData)
	{
		foreach (Image::$thumbnailTemplate as $t) {
			$gdImage = Image::createThumbnailFromImageData($originalImageData, $t[2], $t[0], $t[1] ?? "");

			$fileName = Image::$thumbnailDirectory .
				strtoupper(
					implode(
						"-",
						array_filter([$t[2], $t[0], $t[1]])
					)
				) . "/$assetId." . strtolower($t[0]);

			// Create directory if it does not exist
			$directory = dirname($fileName);
			if (!is_dir($directory)) {
				mkdir($directory, 0755, true);
			}

			// Save image
			match ($t[0]) {
				"JPG" => imagejpeg($gdImage, $fileName, 95),
				"PNG" => imagepng($gdImage, $fileName, 6),
				default => throw new \InvalidArgumentException("Unsupported image format: " . $t[0]),
			};

			Log::write("Saved thumbnail", ["assetId" => $assetId, "fileName" => $fileName]);
		}
	}

	public static function createThumbnailFromImageData(string $rawImageData, int $size, string $extension, string $backgroundColor): GdImage
	{

		Log::write("Building variation", ["size" => $size, "extension" => $extension, "backgroundColor" => $backgroundColor]);

		// Read image using GD
		$tmpImage = imagecreatefromstring($rawImageData);
		$originalWidth = imagesx($tmpImage);
		$originalHeight = imagesy($tmpImage);

		// Calculate new dimensions maintaining aspect ratio
		$ratio = min($size / $originalWidth, $size / $originalHeight);
		$newWidth = (int)($originalWidth * $ratio);
		$newHeight = (int)($originalHeight * $ratio);

		// Calculate offsets to center the image
		$offsetX = (int)(($size - $newWidth) / 2);
		$offsetY = (int)(($size - $newHeight) / 2);

		// Create output image
		$outputImage = imagecreatetruecolor($size, $size);

		if ($backgroundColor ?? "" != "") {
			// Fill with background color
			$r = hexdec(substr($backgroundColor, 0, 2));
			$g = hexdec(substr($backgroundColor, 2, 2));
			$b = hexdec(substr($backgroundColor, 4, 2));
			$bgColor = imagecolorallocate($outputImage, $r, $g, $b);
			imagefill($outputImage, 0, 0, $bgColor);
		} else {
			// Transparent background
			imagealphablending($outputImage, false);
			imagesavealpha($outputImage, true);
			$transparent = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
			imagefill($outputImage, 0, 0, $transparent);
			imagealphablending($outputImage, true);
		}

		// Resize and copy the original image centered
		imagealphablending($tmpImage, true);
		imagecopyresampled($outputImage, $tmpImage, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

		return $outputImage;
	}
}
