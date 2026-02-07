<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thumbnail\Thumbnail;

final class ThumbnailTest extends TestCase
{
	public function testPngThumbnail(): void
	{
		$thumbnail = imagecreatefrompng(__DIR__ . '/test-files/dirt.png');
		$this->assertNotFalse($thumbnail);
		Thumbnail::saveThumbnailVariations(-1, $thumbnail);
	}
	public function testJpgThumbnail(): void
	{
		$thumbnail = imagecreatefromjpeg(__DIR__ . '/test-files/paving.jpg');
		$this->assertNotFalse($thumbnail);
		Thumbnail::saveThumbnailVariations(-1, $thumbnail);
	}
	public function testFullyBlackThumbnail(): void
	{
		$this->expectException(RuntimeException::class);
		$thumbnail = imagecreatefromjpeg(__DIR__ . '/test-files/black.jpg');
		$this->assertNotFalse($thumbnail);
		Thumbnail::saveThumbnailVariations(-1, $thumbnail);
	}
}
