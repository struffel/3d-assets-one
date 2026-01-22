<?php

namespace asset;

use ArrayObject;
use InvalidArgumentException;

/**
 * A collection of `Asset`s.
 * It is used for pagination.
 */
class ScrapedAssetCollection extends ArrayObject
{
	public function __construct(
		array $assets = array(),
		public ?int $pendingToBeScrapedCount = NULL
	) {
		foreach ($assets as $asset) {
			if (!$asset instanceof ScrapedAsset) {
				throw new InvalidArgumentException('All elements must be of type ScrapedAsset');
			}
		}
		parent::__construct($assets);
	}

	public function offsetSet(mixed $key, mixed $value): void
	{
		if (!$value instanceof ScrapedAsset) {
			throw new InvalidArgumentException('All elements must be of type ScrapedAsset');
		}
		parent::offsetSet($key, $value);
	}

	public function append(mixed $value): void
	{
		if (!$value instanceof ScrapedAsset) {
			throw new InvalidArgumentException('All elements must be of type ScrapedAsset');
		}
		parent::append($value);
	}
}
