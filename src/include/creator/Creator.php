<?php

namespace creator;

use asset\CommonLicense;
use creator\CreatorLogic;
use creator\logic\CreatorLogicAmbientCg;
use creator\logic\CreatorLogic3dTextures;
use creator\logic\CreatorLogicPolyhaven;
use creator\logic\CreatorLogicShareTextures;
use creator\logic\CreatorLogicCgBookcase;
use creator\logic\CreatorLogicTextureCan;
use creator\logic\CreatorLogicNoEmotionsHdr;
use creator\logic\CreatorLogicAmdGpuOpen;
use creator\logic\CreatorLogicRawCatalog;
use creator\logic\CreatorLogicHdriWorkshop;
use creator\logic\CreatorLogicPbrPx;
use creator\logic\CreatorLogicPoliigon;
use creator\logic\CreatorLogicTexturesCom;
use creator\logic\CreatorLogicCgMood;
use creator\logic\CreatorLogicThreeDScans;
use creator\logic\CreatorLogicLocationTextures;
use creator\logic\CreatorLogicTwinbru;
use creator\logic\CreatorLogicLightbeans;
use Exception;
use InvalidArgumentException;

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
	case POLIIGON = 14;
	case TEXTURES_COM = 15;
	case CGMOOD = 16;
	case THREE_D_SCANS = 18;
	case LOCATION_TEXTURES = 19;
	case PBR_PX = 20;
	case TWINBRU = 21;
	case LIGHTBEANS = 22;

	public static function fromAny(mixed $value): self
	{
		if (is_numeric($value)) {
			return self::from(intval($value));
		} elseif (is_string($value)) {
			return self::fromSlug($value);
		} else {
			throw new InvalidArgumentException("Cannot convert value to Creator enum.");
		}
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

	public function title(): string
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

	public function commonLicense(): CommonLicense
	{
		return match ($this) {
			self::AMBIENTCG => CommonLicense::CC0,
			self::POLYHAVEN => CommonLicense::CC0,
			self::SHARETEXTURES => CommonLicense::CC0,
			self::TEXTURECAN => CommonLicense::CC0,
			self::CGBOOKCASE => CommonLicense::CC0,
			self::NOEMOTIONHDRS => CommonLicense::CC_BY_ND,
			self::GPUOPENMATLIB => CommonLicense::APACHE_2_0,
			default => CommonLicense::NONE
		};
	}

	public function licenseUrl(): ?string
	{
		return match ($this) {
			self::AMBIENTCG => 'https://docs.ambientcg.com/license/',
			self::LOCATION_TEXTURES => 'https://locationtextures.com/privacy-policy/',
			default => NULL
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

	// Additional helpers

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
			self::LIGHTBEANS,
			self::NOEMOTIONHDRS
		];
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

	public function getLogic(): CreatorLogic
	{
		return match ($this) {
			self::AMBIENTCG => new CreatorLogicAmbientCg(),
			self::POLYHAVEN => new CreatorLogicPolyhaven(),
			self::SHARETEXTURES => new CreatorLogicShareTextures(),
			self::THREE_D_TEXTURES => new CreatorLogic3dTextures(),
			self::CGBOOKCASE => new CreatorLogicCgBookcase(),
			self::TEXTURECAN => new CreatorLogicTextureCan(),
			self::NOEMOTIONHDRS => new CreatorLogicNoEmotionsHdr(),
			self::GPUOPENMATLIB => new CreatorLogicAmdGpuOpen(),
			self::RAWCATALOG => new CreatorLogicRawCatalog(),
			self::POLIIGON => new CreatorLogicPoliigon(),
			self::TEXTURES_COM => new CreatorLogicTexturesCom(),
			self::CGMOOD => new CreatorLogicCgMood(),
			self::THREE_D_SCANS => new CreatorLogicThreeDScans(),
			self::LOCATION_TEXTURES => new CreatorLogicLocationTextures(),
			self::PBR_PX => new CreatorLogicPbrPx(),
			self::TWINBRU => new CreatorLogicTwinbru(),
			self::LIGHTBEANS => new CreatorLogicLightbeans(),
			default => throw new InvalidArgumentException("No logic defined for creator " . $this->title()),
		};
	}
}
