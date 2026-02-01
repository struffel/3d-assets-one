<?php

namespace asset;

use ArrayObject;
use InvalidArgumentException;

/**
 * A collection of `Asset`s.
 * It is used for pagination.
 * @extends ArrayObject<int, StoredAsset>
 */
class StoredAssetCollection extends ArrayObject
{
	use TestForUrl;

	/**
	 * @param array<mixed> $assets 
	 * @param null|StoredAssetQuery $nextCollection 
	 * @throws InvalidArgumentException 
	 */
	public function __construct(
		array $assets = array(),
		public ?StoredAssetQuery $nextCollection = NULL
	) {
		foreach ($assets as $asset) {
			if (!$asset instanceof StoredAsset) {
				throw new InvalidArgumentException('All elements must be of type StoredAsset');
			}
		}
		/** @var array<StoredAsset> $assets */
		parent::__construct($assets);
	}

	public function offsetSet(mixed $key, mixed $value): void
	{
		if (!$value instanceof StoredAsset) {
			throw new InvalidArgumentException('All elements must be of type StoredAsset');
		}
		parent::offsetSet($key, $value);
	}

	public function append(mixed $value): void
	{
		if (!$value instanceof StoredAsset) {
			throw new InvalidArgumentException('All elements must be of type StoredAsset');
		}
		parent::append($value);
	}
}
