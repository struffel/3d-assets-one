<?php

namespace asset;

trait TestForUrl
{

	/**
	 * Tests whether any of the {@link StoredAsset}s in this collection have the given URL.
	 * Ignoring capitalization.
	 * @param string $url 
	 * @return bool 
	 */
	public function containsUrl(string $url): bool
	{
		foreach ($this as $asset) {
			if (strtolower($asset->url) == strtolower($url)) {
				return true;
			}
		}
		return false;
	}
}
