<?php

namespace thumbnail;

use asset\Asset;
use database\Database;
use log\Log;

use GdImage;
use log\LogLevel;
use RuntimeException;
use SQLite3Result;
use thumbnail\ThumbnailFormat;

class Thumbnail
{

	private static function getThumbnailStorePath(): string
	{
		return __DIR__ . '/../../public/thumbnail';
	}

	/**
	 * Deletes all thumbnail variations carrying asset ids no longer in the database.
	 * @return void 
	 */
	public static function deleteOrphanedThumbnails(): void
	{
		// Do nothing if the thumbnail directory does not exist
		if (!is_dir(self::getThumbnailStorePath())) {
			return;
		}

		// Get all existing asset IDs from the database
		$existingIds = [];
		$dbResult = Database::runQuery("SELECT id FROM Asset");
		assert($dbResult instanceof SQLite3Result);
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
					Log::write("Deleted orphaned thumbnail", $fullVariationDir . $file, LogLevel::DEBUG);
				}
			}
		}
	}

	public static function saveThumbnailVariations(int $assetId, GdImage $originalImage): void
	{

		// Is the input image valid?
		self::validateImage($originalImage);

		foreach (ThumbnailFormat::cases() as $t) {

			$gdImage = Thumbnail::createThumbnailFromImageData($originalImage, $t);

			$fileName = self::getThumbnailStorePath() . "/" .
				$t->value . "/$assetId." . strtolower($t->getExtension());

			// Create directory if it does not exist
			$directory = dirname($fileName);
			if (!is_dir($directory)) {
				mkdir($directory, 0755, true);
			}

			// Save image
			match ($t->getExtension()) {
				"JPG" => imagejpeg($gdImage, $fileName, 95),
				"PNG" => imagepng($gdImage, $fileName, 6),
				default => throw new \InvalidArgumentException("Unsupported image format: " . $t->getExtension()),
			};

			self::validateImageFile($fileName);

			Log::write("Saved thumbnail", ["assetId" => $assetId, "fileName" => $fileName], LogLevel::DEBUG);
		}
	}

	private static function validateImageFile(string $filePath): void
	{
		// Exists?
		if (!file_exists($filePath)) {
			throw new RuntimeException("Thumbnail file does not exist: " . $filePath);
		}

		// Not empty?
		if (filesize($filePath) === 0) {
			throw new RuntimeException("Thumbnail file is empty: " . $filePath);
		}

		$imageInfo = getimagesize($filePath);
		if ($imageInfo === false) {
			throw new RuntimeException("Failed to get image info for thumbnail: " . $filePath);
		}

		$gdImage = match ($imageInfo[2]) {
			IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
			IMAGETYPE_PNG => imagecreatefrompng($filePath),
			default => throw new RuntimeException("Unsupported thumbnail image type: " . $filePath),
		};

		if ($gdImage === false) {
			throw new RuntimeException("Failed to create GD image for validation purposes: " . $filePath);
		}

		self::validateImage($gdImage);
	}

	private static function validateImage(GdImage $gdImage): void
	{

		// Not identical on all pixels?
		// I.e. is the entire image black/white/transparent?

		$width = imagesx($gdImage);
		$height = imagesy($gdImage);

		if ($width === 0 || $height === 0) {
			throw new RuntimeException("Thumbnail image has zero width or height, likely invalid.");
		}

		$checkInterval = max(1, min((int)($width / 10), (int)($height / 10)));
		$allPixelsSame = true;
		$firstPixel = imagecolorat($gdImage, 0, 0);
		for ($x = 0; $x < $width; $x += $checkInterval) {
			for ($y = 0; $y < $height; $y += $checkInterval) {
				if (imagecolorat($gdImage, $x, $y) !== $firstPixel) {
					$allPixelsSame = false;
					break 2;
				}
			}
		}

		if ($allPixelsSame) {
			throw new RuntimeException("Thumbnail image is uniformly colored and likely invalid");
		}
	}

	public static function createThumbnailFromImageData(GdImage $rawImage, ThumbnailFormat $format): GdImage
	{

		Log::write("Building variation " . $format->value, LogLevel::DEBUG);

		$originalWidth = imagesx($rawImage);
		$originalHeight = imagesy($rawImage);

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
		imagealphablending($rawImage, true);
		imagecopyresampled($outputImage, $rawImage, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

		return $outputImage;
	}
}
