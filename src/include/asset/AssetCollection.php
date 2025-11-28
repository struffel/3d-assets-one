<?php

namespace asset;

/**
 * A collection of `Asset`s.
 * It is used for pagination.
 */
class AssetCollection
{
	public function __construct(
		public array $assets = array(),
		public ?int $totalNumberOfAssetsInBackend = NULL,
		public ?AssetQuery $nextCollection = NULL
	) {}
}
