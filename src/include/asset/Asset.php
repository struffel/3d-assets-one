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
		public DateTime $date,
		public Type $type,
		public License $license,
		public Creator $creator,
		public array $tags = [],
		public AssetStatus $status = AssetStatus::ACTIVE,
		public ?DateTime $lastSuccessfulValidation = NULL,
		public ?string $rawThumbnailData = NULL
	) {}

	public function getThumbnailUrl(int $size, string $extension, ?string $backgroundColor): string
	{
		$variation = strtoupper(implode("-", array_filter([$size, $extension, $backgroundColor])));
		$extension = strtolower($extension);
		$id = $this->id;
		return "/img/thumbnail/$variation/$id.$extension";
	}
}
