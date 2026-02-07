<?php

namespace asset;

enum ScrapedAssetStatus: int
{
	case NEWLY_FOUND = 0;
	case UPDATED = 1;
	case VALIDATED = 2;

	case NEWLY_FOUND_FAILED = 100;
	case UPDATED_FAILED = 101;
	case VALIDATED_FAILED = 102;

	public function toStoredAssetStatus(): StoredAssetStatus
	{
		return match ($this) {
			ScrapedAssetStatus::NEWLY_FOUND,
			ScrapedAssetStatus::UPDATED,
			ScrapedAssetStatus::VALIDATED => StoredAssetStatus::ACTIVE,
			ScrapedAssetStatus::NEWLY_FOUND_FAILED,
			ScrapedAssetStatus::UPDATED_FAILED => StoredAssetStatus::VALIDATION_FAILED_RECENTLY,
			ScrapedAssetStatus::VALIDATED_FAILED => StoredAssetStatus::VALIDATION_FAILED_RECENTLY,
		};
	}
}
