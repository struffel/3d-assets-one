<?php

use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use creator\logic\CreatorLogic3dTextures;
use creator\logic\CreatorLogicAmbientCg;
use creator\logic\CreatorLogicAmdGpuOpen;
use creator\logic\CreatorLogicCgBookcase;
use creator\logic\CreatorLogicCgMood;
use creator\logic\CreatorLogicLightbeans;
use creator\logic\CreatorLogicLocationTextures;
use creator\logic\CreatorLogicNoEmotionsHdr;
use creator\logic\CreatorLogicPbrPx;
use creator\logic\CreatorLogicPoliigon;
use creator\logic\CreatorLogicPolyhaven;
use creator\logic\CreatorLogicRawCatalog;
use creator\logic\CreatorLogicShareTextures;
use creator\logic\CreatorLogicTextureCan;
use creator\logic\CreatorLogicTexturesCom;
use creator\logic\CreatorLogicThreeDScans;
use creator\logic\CreatorLogicTwinbru;
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
