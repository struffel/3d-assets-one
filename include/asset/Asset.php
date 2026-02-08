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
	/**
	 * 
	 * @param null|int $id 
	 * @param null|string $creatorGivenId 
	 * @param string $title 
	 * @param string $url 
	 * @param AssetType $type 
	 * @param Creator $creator 
	 * @param array<string> $tags 
	 * @return void 
	 */
	public function __construct(
		public ?int $id,
		public ?string $creatorGivenId,
		public string $title,
		public string $url,
		public AssetType $type,
		public Creator $creator,
		public array $tags = [],
	) {}
}
