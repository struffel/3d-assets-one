<?php

use asset\StoredAssetCollection;
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
		return [
			'3dTextures' => [new CreatorLogic3dTextures()],
			'AmbientCg' => [new CreatorLogicAmbientCg()],
			'AmdGpuOpen' => [new CreatorLogicAmdGpuOpen()],
			'CgBookcase' => [new CreatorLogicCgBookcase()],
			'CgMood' => [new CreatorLogicCgMood()],
			'Lightbeans' => [new CreatorLogicLightbeans()],
			'LocationTextures' => [new CreatorLogicLocationTextures()],
			'NoEmotionsHdr' => [new CreatorLogicNoEmotionsHdr()],
			'PbrPx' => [new CreatorLogicPbrPx()],
			'Poliigon' => [new CreatorLogicPoliigon()],
			'Polyhaven' => [new CreatorLogicPolyhaven()],
			'RawCatalog' => [new CreatorLogicRawCatalog()],
			'ShareTextures' => [new CreatorLogicShareTextures()],
			'TextureCan' => [new CreatorLogicTextureCan()],
			'TexturesCom' => [new CreatorLogicTexturesCom()],
			'ThreeDScans' => [new CreatorLogicThreeDScans()],
			'Twinbru' => [new CreatorLogicTwinbru()],
		];
	}

	#[DataProvider('creatorLogicProvider')]
	public function testScrapeCreatorLogic(CreatorLogic $creatorLogic): void
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
