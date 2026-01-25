<?php

namespace log;

enum EventType: int
{
	case ASSET_ADDED_NEW = 1;
	case ASSET_UPDATED = 2;
	case ASSET_DELETED = 3;
	case ASSET_VALIDATED = 4;
	case ASSET_VALIDATION_FAILED = 5;
	case SCRAPE_RUN_COMPLETED = 6;
	case SCRAPE_RUN_FAILED = 7;

	public function name(): string
	{
		return match ($this) {
			EventType::ASSET_ADDED_NEW => 'New Asset Added',
			EventType::ASSET_UPDATED => 'Asset Updated',
			EventType::ASSET_DELETED => 'Asset Deleted',
			EventType::ASSET_VALIDATED => 'Asset Validated',
			EventType::ASSET_VALIDATION_FAILED => 'Asset Validation Failed',
			EventType::SCRAPE_RUN_COMPLETED => 'Scrape Run Completed',
			EventType::SCRAPE_RUN_FAILED => 'Scrape Run Failed',
		};
	}
}
