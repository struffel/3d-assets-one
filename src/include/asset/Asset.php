<?php

namespace asset;

use creator\Creator;
use DateTime;

/**
 * The main asset class.
 * It represents one PBR material, 3D model or other asset.
 */
class Asset
{
	public function __construct(
		public ?int $id,
		public string $name,
		public string $url,
		public string $thumbnailUrl,
		public string $date,
		public Type $type,
		public License $license,
		public Creator $creator,
		public array $tags = [],
		public array $quirks = [],	// Array of QUIRK
		public AssetStatus $status = AssetStatus::PENDING,
		public ?DateTime $lastSuccessfulValidation = NULL
	) {}
}
