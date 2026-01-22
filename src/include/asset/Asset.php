<?php

namespace asset;

use creator\Creator;
use DateTime;

/**
 * The main asset class.
 * It represents one PBR material, 3D model or other asset.
 */
abstract class Asset
{
	public function __construct(
		public ?int $id,
		public ?string $creatorGivenId,
		public string $title,
		public string $url,
		public DateTime $date,
		public AssetType $type,
		public Creator $creator,
		public array $tags = [],
	) {}
}
