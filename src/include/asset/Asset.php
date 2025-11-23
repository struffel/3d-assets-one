<?php

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
		public array $tags = [],
		public TYPE $type,
		public LICENSE $license,
		public CREATOR $creator,
		public array $quirks = [],	// Array of QUIRK
		public AssetStatus $status = AssetStatus::PENDING,
		public ?DateTime $lastSuccessfulValidation = NULL
	) {}
}
