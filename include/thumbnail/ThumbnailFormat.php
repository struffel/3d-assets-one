<?php

namespace thumbnail;

enum ThumbnailFormat: string
{
	case JPG_32_FFFFFF = "32-JPG-FFFFFF";
	case JPG_64_FFFFFF = "64-JPG-FFFFFF";
	case JPG_128_FFFFFF = "128-JPG-FFFFFF";
	case JPG_256_FFFFFF = "256-JPG-FFFFFF";
	case PNG_32 = "32-PNG";
	case PNG_64 = "64-PNG";
	case PNG_128 = "128-PNG";
	case PNG_256 = "256-PNG";

	/**
	 * 
	 * @return int<1, max> Size in pixels 
	 */
	public function getSize(): int
	{
		return match ($this) {
			self::JPG_32_FFFFFF, self::PNG_32 => 32,
			self::JPG_64_FFFFFF, self::PNG_64 => 64,
			self::JPG_128_FFFFFF, self::PNG_128 => 128,
			self::JPG_256_FFFFFF, self::PNG_256 => 256,
		};
	}

	public function getExtension(): string
	{
		return match ($this) {
			self::JPG_32_FFFFFF, self::JPG_64_FFFFFF, self::JPG_128_FFFFFF, self::JPG_256_FFFFFF => "JPG",
			self::PNG_32, self::PNG_64, self::PNG_128, self::PNG_256 => "PNG",
		};
	}

	public function getBackgroundColorHex(): ?string
	{
		return match ($this) {
			self::JPG_32_FFFFFF, self::JPG_64_FFFFFF, self::JPG_128_FFFFFF, self::JPG_256_FFFFFF => "FFFFFF",
			self::PNG_32, self::PNG_64, self::PNG_128, self::PNG_256 => NULL,
		};
	}
}
