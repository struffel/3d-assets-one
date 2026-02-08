<?php

use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use log\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CreatorLogicTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		Log::start("tests/creator-logic/" . Log::timestampHelper(), false);
	}

	public static function creatorLogicProvider(): array
	{
		$output = [];
		foreach (Creator::cases() as $creator) {
			$output[$creator->slug()] = [$creator->getLogic()];
		}
		return $output;
	}

	#[DataProvider('creatorLogicProvider')]
	public function testScrapeAssets(CreatorLogic $creatorLogic): void
	{
		$existingAssets = new StoredAssetCollection();
		$scrapedAssets = $creatorLogic->scrapeAssets($existingAssets);
		$this->assertGreaterThan(0, sizeof($scrapedAssets));
		$scrapedAssets = NULL;
	}

	public static function tearDownAfterClass(): void
	{
		Log::stop(true);
	}
}
