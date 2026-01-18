<?php

namespace creator;

use indexing\CreatorIndexer;

enum Creator: int
{
	case AMBIENTCG = 1;
	case POLYHAVEN = 2;
	case SHARETEXTURES = 3;
	case THREE_D_TEXTURES = 4;
	case CGBOOKCASE = 5;
	case TEXTURECAN = 6;
	case NOEMOTIONHDRS = 7;
	case GPUOPENMATLIB = 10;
	case RAWCATALOG = 11;
	case HDRIWORKSHOP = 12;
	case PBRMATERIALS = 13;
	case POLIIGON = 14;
	case TEXTURES_COM = 15;
	case CGMOOD = 16;
	case THREE_D_SCANS = 18;
	case LOCATION_TEXTURES = 19;
	case PBR_PX = 20;
	case TWINBRU = 21;
	case LIGHTBEANS = 22;

	public static function regularRefreshList(): array
	{
		return [
			self::AMBIENTCG,
			self::POLYHAVEN,
			self::SHARETEXTURES,
			self::THREE_D_TEXTURES,
			self::CGBOOKCASE,
			self::TEXTURECAN,
			self::GPUOPENMATLIB,
			self::RAWCATALOG,
			self::POLIIGON,
			self::TEXTURES_COM,
			self::CGMOOD,
			self::THREE_D_SCANS,
			self::LOCATION_TEXTURES,
			self::PBR_PX,
			self::TWINBRU,
			self::LIGHTBEANS
		];
	}

	public function slug(): string
	{
		return match ($this) {
			self::AMBIENTCG => 'ambientcg',
			self::POLYHAVEN => 'polyhaven',
			self::SHARETEXTURES => 'sharetextures',
			self::TEXTURECAN => 'texturecan',
			self::THREE_D_TEXTURES => '3d-textures',
			self::CGBOOKCASE => 'cgbookcase',
			self::NOEMOTIONHDRS => 'noemotionhdrs',
			self::GPUOPENMATLIB => 'gpuopen-matlib',
			self::RAWCATALOG => 'rawcatalog',
			self::HDRIWORKSHOP => 'hdri-workshop',
			self::PBRMATERIALS => 'pbrmaterials-com',
			self::POLIIGON => 'poliigon',
			self::TEXTURES_COM => 'textures-com',
			self::CGMOOD => 'cgmood',
			self::THREE_D_SCANS => 'three-d-scans',
			self::LOCATION_TEXTURES => 'location-textures',
			self::PBR_PX => "pbr-px",
			self::TWINBRU => "twinbru",
			self::LIGHTBEANS => "lightbeans"
		};
	}

	public function name(): string
	{
		return match ($this) {
			self::AMBIENTCG => 'ambientCG',
			self::POLYHAVEN => 'Poly Haven',
			self::SHARETEXTURES => 'Share Textures',
			self::THREE_D_TEXTURES => '3D Textures',
			self::TEXTURECAN => 'Texture Can',
			self::CGBOOKCASE => 'CG Bookcase',
			self::NOEMOTIONHDRS => 'NoEmotion HDRs',
			self::GPUOPENMATLIB => 'AMD GPUOpen MaterialX Library',
			self::RAWCATALOG => 'Raw Catalog',
			self::HDRIWORKSHOP => 'HDRI Workshop',
			self::PBRMATERIALS => 'PBRMaterials.com',
			self::POLIIGON => 'Poliigon (Free Section)',
			self::TEXTURES_COM => 'Textures.com (Free Section)',
			self::CGMOOD => 'CGMood (Free Section)',
			self::THREE_D_SCANS => 'Three D Scans',
			self::LOCATION_TEXTURES => 'Location Textures',
			self::PBR_PX => 'PBRPX',
			self::TWINBRU => 'Twinbru',
			self::LIGHTBEANS => 'Lightbeans'
		};
	}

	public function description(): string
	{
		return match ($this) {
			self::THREE_D_TEXTURES => 'Free seamless PBR textures and unique creations in Substance Designer.',
			self::AMBIENTCG => '2000+ Public Domain materials, HDRIs and models for Physically Based Rendering.',
			self::POLYHAVEN => 'The Public 3D Asset Library - A combination of the websites "HDRI Haven", "Texture Haven" and "3D Model Haven."',
			self::SHARETEXTURES => 'ShareTextures.com is creating and sharing PBR textures since 2018.',
			self::TEXTURECAN => 'Offers free CG textures, free graphics and free patterns for 3D artists.',
			self::CGBOOKCASE => 'Free PBR textures that come with all the map types needed to create photorealistic materials.',
			self::NOEMOTIONHDRS => 'An older website with an impressive collection of free HDRIs.',
			self::GPUOPENMATLIB => 'A collection of high-quality materials and related textures that is available completely for free, hosted by AMD GPUOpen. (Duplicates of materials from Polyhaven are excluded.)',
			self::RAWCATALOG => 'A unique library that includes many ready-to-use resources for creating amazing projects in the field of video games, films, animation and visualization.',
			self::HDRIWORKSHOP => 'Royalty free, high quality HDRIs with unclipped sun, up to 29 EV range and camera background photos from the location!',
			self::PBRMATERIALS => 'PBRMaterials.com, founded in 2022, is dedicated to providing high-end scanned and Substance Designer assets for 3D artists.',
			self::POLIIGON => 'Textures, models and HDRIs for photorealistic 3D rendering. Make better renders, faster. Currently, only the "Free" section is indexed.',
			self::TEXTURES_COM => 'Take your CG art to the next level with our highest quality content! Currently, only the "Free" section is indexed.',
			self::CGMOOD => 'CGMood is a fresh, fair 3D marketplace. We are a team of architects and designers with many years of experience in the 3D visualization field. Currently, only the "Free" section is indexed.',
			self::THREE_D_SCANS => 'A collection of high-quality statues/sculptures scanned in various european museums.',
			self::LOCATION_TEXTURES => 'Locationtextures.com is an online platform providing high quality royalty-free photo reference packs for games and film industry. We offer free packs and every pack comes with free samples.',
			self::PBR_PX => 'We are a small team from China, passionate about CG production. Through PBRPX, we provide artists with completely free, unrestricted digital assets, allowing them to unleash their creativity.',
			self::TWINBRU => 'Browse our library of more than 13 000 digital fabric twins to download 3D fabric textures or order physical fabric samples.',
			self::LIGHTBEANS => 'We Connect Manufacturers with Architects and Designers - Thousands of digitized products for your projects.'
		};
	}

	public function licenseUrl(): string
	{
		return match ($this) {
			self::AMBIENTCG => 'https://docs.ambientcg.com/license/',
			self::LOCATION_TEXTURES => 'https://locationtextures.com/privacy-policy/',
			default => ""
		};
	}

	public function baseUrl(): string
	{
		return match ($this) {
			self::THREE_D_TEXTURES => "https://3dtextures.me",
			self::AMBIENTCG => 'https://ambientCG.com',
			self::POLYHAVEN => 'https://polyhaven.com',
			self::SHARETEXTURES => 'https://sharetextures.com',
			self::TEXTURECAN => 'https://texturecan.com',
			self::CGBOOKCASE => 'https://cgbookcase.com',
			self::NOEMOTIONHDRS => 'http://noemotionhdrs.net',
			self::GPUOPENMATLIB => 'https://matlib.gpuopen.com/',
			self::RAWCATALOG => 'https://rawcatalog.com',
			self::HDRIWORKSHOP => 'https://hdri-workshop.com/',
			self::PBRMATERIALS => 'https://pbrmaterials.com',
			self::POLIIGON => 'https://www.poliigon.com/search/free',
			self::TEXTURES_COM => 'https://www.textures.com/free',
			self::CGMOOD => 'https://cgmood.com/free',
			self::THREE_D_SCANS => 'https://threedscans.com/',
			self::LOCATION_TEXTURES => 'https://locationtextures.com/panoramas/free-panoramas/',
			self::PBR_PX => 'https://library.pbrpx.com/',
			self::TWINBRU => 'https://textures.twinbru.com',
			self::LIGHTBEANS => 'https://lightbeans.com'
		};
	}

	public static function fromSlug(string $slug): ?self
	{
		foreach (self::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return null;
	}

	public function getIndexer(): ?CreatorIndexer
	{
		return match ($this) {
			self::AMBIENTCG => new \indexing\creator\CreatorIndexerAmbientCg(),
			self::POLYHAVEN => new \indexing\creator\CreatorIndexerPolyhaven(),
			self::SHARETEXTURES => new \indexing\creator\CreatorIndexerShareTextures(),
			self::THREE_D_TEXTURES => new \indexing\creator\CreatorIndexer3dTextures(),
			self::CGBOOKCASE => new \indexing\creator\CreatorIndexerCgBookcase(),
			self::TEXTURECAN => new \indexing\creator\CreatorIndexerTextureCan(),
			self::NOEMOTIONHDRS => new \indexing\creator\CreatorIndexerNoEmotionsHdr(),
			self::GPUOPENMATLIB => new \indexing\creator\CreatorIndexerAmdMaterialX(),
			self::RAWCATALOG => new \indexing\creator\CreatorIndexerRawCatalog(),
			self::HDRIWORKSHOP => new \indexing\creator\CreatorIndexerHdriWorkshop(),
			self::POLIIGON => new \indexing\creator\CreatorIndexerPoliigon(),
			self::TEXTURES_COM => new \indexing\creator\CreatorIndexerTexturesCom(),
			self::CGMOOD => new \indexing\creator\CreatorIndexerCgMood(),
			self::THREE_D_SCANS => new \indexing\creator\CreatorIndexerThreeDScans(),
			self::LOCATION_TEXTURES => new \indexing\creator\CreatorIndexerLocationTextures(),
			self::PBR_PX => new \indexing\creator\CreatorIndexerPbrPx(),
			self::TWINBRU => new \indexing\creator\CreatorIndexerTwinbru(),
			self::LIGHTBEANS => new \indexing\creator\CreatorIndexerLightbeans(),
			default => null
		};
	}
}
