<?php

namespace asset;

enum StoredAssetStatus: int
{

	/**
	 * The asset is not active and should likely never be activated.
	 * This may be, for example, because the asset fetching function for that creator
	 * erroneously detects a certain page as an asset and keeping it in the DB is easier
	 * than adding all relevant edge cases to the fetching function.
	 */
	case MANUALLY_BLOCKED = -1;

	/**
	 * The asset is not active and awaits activation.
	 * This happens with a freshly registered asset that has not yet had its thumbnail processed.
	 */
	case PENDING = 0;

	/**
	 * The asset is active and can be found in regular searches.
	 */
	case ACTIVE = 1;

	/**
	 * The asset is not active because it has failed its validation check.
	 */
	case VALIDATION_FAILED_RECENTLY = -2;

	/**
	 * The a
	 */
	case VALIDATION_FAILED_PERMANENTLY = -3;
}
