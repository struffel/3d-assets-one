<?php

namespace thumbnail;

use asset\Asset;
use database\Database;
use log\Log;

use GdImage;
use RuntimeException;
use thumbnail\ThumbnailFormat;

class Thumbnail
{

	private static function getThumbnailStorePath(): string
	{
		return __DIR__ . '/../../public/thumbnail';
	}

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
		// Do nothing if the thumbnail directory does not exist
		if (!is_dir(self::getThumbnailStorePath())) {
			return;
		}

		$existingIds = [];
		$dbResult = Database::runQuery("SELECT id FROM Asset");
		while ($row = $dbResult->fetchArray()) {
			$existingIds[] = $row['id'];
		}
		$thumbnailDir = self::getThumbnailStorePath() . "/";
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
					Log::write("Deleted orphaned thumbnail", $fullVariationDir . $file);
				}
			}
		}
	}

	public static function saveThumbnailVariations(int $assetId, string $originalImageData)
	{
		foreach (ThumbnailFormat::cases() as $t) {
			$gdImage = Thumbnail::createThumbnailFromImageData($originalImageData, $t[2], $t[0], $t[1] ?? "");

			$fileName = self::getThumbnailStorePath() . "/" .
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

	public static function createThumbnailFromImageData(string $rawImageData, ThumbnailFormat $format): GdImage
	{

		Log::write("Building variation " . $format->value);

		// Read image using GD
		$tmpImage = imagecreatefromstring($rawImageData);

		if ($tmpImage === false) {
			throw new RuntimeException("Failed to create image from data.");
		}

		$originalWidth = imagesx($tmpImage);
		$originalHeight = imagesy($tmpImage);

		// Calculate new dimensions maintaining aspect ratio
		$ratio = min($format->getSize() / $originalWidth, $format->getSize() / $originalHeight);
		$newWidth = (int)($originalWidth * $ratio);
		$newHeight = (int)($originalHeight * $ratio);

		// Calculate offsets to center the image
		$offsetX = (int)(($format->getSize() - $newWidth) / 2);
		$offsetY = (int)(($format->getSize() - $newHeight) / 2);

		// Create output image
		$outputImage = imagecreatetruecolor($format->getSize(), $format->getSize());

		if ($format->getBackgroundColorHex() !== NULL) {

			// Fill with background color
			$r = max(min(intval(substr($format->getBackgroundColorHex(), 0, 2), 16), 255), 0);
			$g = max(min(intval(substr($format->getBackgroundColorHex(), 2, 2), 16), 255), 0);
			$b = max(min(intval(substr($format->getBackgroundColorHex(), 4, 2), 16), 255), 0);
			$bgColor = imagecolorallocate($outputImage, $r, $g, $b);
			if ($bgColor === false) {
				throw new RuntimeException("Failed to allocate background color.");
			}
			imagefill($outputImage, 0, 0, $bgColor);
		} else {

			// Transparent background
			imagealphablending($outputImage, false);
			imagesavealpha($outputImage, true);
			$transparent = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
			if ($transparent === false) {
				throw new RuntimeException("Failed to allocate transparent color.");
			}
			imagefill($outputImage, 0, 0, $transparent);
			imagealphablending($outputImage, true);
		}

		// Resize and copy the original image centered
		imagealphablending($tmpImage, true);
		imagecopyresampled($outputImage, $tmpImage, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

		return $outputImage;
	}
}
