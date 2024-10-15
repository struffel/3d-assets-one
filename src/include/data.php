<?php

enum CREATOR: int
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

	public static function regularRefreshList(): array
	{
		return [
			CREATOR::AMBIENTCG,
			CREATOR::POLYHAVEN,
			CREATOR::SHARETEXTURES,
			CREATOR::THREE_D_TEXTURES,
			CREATOR::CGBOOKCASE,
			CREATOR::TEXTURECAN,
			CREATOR::GPUOPENMATLIB,
			CREATOR::RAWCATALOG,
			CREATOR::PBRMATERIALS,
			CREATOR::POLIIGON,
			CREATOR::TEXTURES_COM,
			CREATOR::CGMOOD,
			CREATOR::THREE_D_SCANS,
			CREATOR::LOCATION_TEXTURES,
			CREATOR::PBR_PX,
			CREATOR::TWINBRU
		];
	}

	public function slug(): string
	{
		return match ($this) {
			CREATOR::AMBIENTCG => 'ambientcg',
			CREATOR::POLYHAVEN => 'polyhaven',
			CREATOR::SHARETEXTURES => 'sharetextures',
			CREATOR::TEXTURECAN => 'texturecan',
			CREATOR::THREE_D_TEXTURES => '3d-textures',
			CREATOR::CGBOOKCASE => 'cgbookcase',
			CREATOR::NOEMOTIONHDRS => 'noemotionhdrs',
			CREATOR::GPUOPENMATLIB => 'gpuopen-matlib',
			CREATOR::RAWCATALOG => 'rawcatalog',
			CREATOR::HDRIWORKSHOP => 'hdri-workshop',
			CREATOR::PBRMATERIALS => 'pbrmaterials-com',
			CREATOR::POLIIGON => 'poliigon',
			CREATOR::TEXTURES_COM => 'textures-com',
			CREATOR::CGMOOD => 'cgmood',
			CREATOR::THREE_D_SCANS => 'three-d-scans',
			CREATOR::LOCATION_TEXTURES => 'location-textures',
			CREATOR::PBR_PX => "pbr-px",
			CREATOR::TWINBRU => "twinbru"
		};
	}

	public function name(): string
	{
		return match ($this) {
			CREATOR::AMBIENTCG => 'ambientCG',
			CREATOR::POLYHAVEN => 'Poly Haven',
			CREATOR::SHARETEXTURES => 'Share Textures',
			CREATOR::THREE_D_TEXTURES => '3D Textures',
			CREATOR::TEXTURECAN => 'Texture Can',
			CREATOR::CGBOOKCASE => 'CG Bookcase',
			CREATOR::NOEMOTIONHDRS => 'NoEmotion HDRs',
			CREATOR::GPUOPENMATLIB => 'AMD GPUOpen MaterialX Library',
			CREATOR::RAWCATALOG => 'Raw Catalog',
			CREATOR::HDRIWORKSHOP => 'HDRI Workshop',
			CREATOR::PBRMATERIALS => 'PBRMaterials.com',
			CREATOR::POLIIGON => 'Poliigon (Free Section)',
			CREATOR::TEXTURES_COM => 'Textures.com (Free Section)',
			CREATOR::CGMOOD => 'CGMood (Free Section)',
			CREATOR::THREE_D_SCANS => 'Three D Scans',
			CREATOR::LOCATION_TEXTURES => 'Location Textures',
			CREATOR::PBR_PX => 'PBRPX',
			CREATOR::TWINBRU => 'Twinbru'
		};
	}

	public function description(): string
	{
		return match ($this) {
			CREATOR::THREE_D_TEXTURES => 'Free seamless PBR textures and unique creations in Substance Designer.',
			CREATOR::AMBIENTCG => '2000+ Public Domain materials, HDRIs and models for Physically Based Rendering.',
			CREATOR::POLYHAVEN => 'The Public 3D Asset Library - A combination of the websites "HDRI Haven", "Texture Haven" and "3D Model Haven."',
			CREATOR::SHARETEXTURES => 'ShareTextures.com is creating and sharing PBR textures since 2018.',
			CREATOR::TEXTURECAN => 'Offers free CG textures, free graphics and free patterns for 3D artists.',
			CREATOR::CGBOOKCASE => 'Free PBR textures that come with all the map types needed to create photorealistic materials.',
			CREATOR::NOEMOTIONHDRS => 'An older website with an impressive collection of free HDRIs.',
			CREATOR::GPUOPENMATLIB => 'A collection of high-quality materials and related textures that is available completely for free, hosted by AMD GPUOpen. (Duplicates of materials from Polyhaven are excluded.)',
			CREATOR::RAWCATALOG => 'A unique library that includes many ready-to-use resources for creating amazing projects in the field of video games, films, animation and visualization.',
			CREATOR::HDRIWORKSHOP => 'Royalty free, high quality HDRIs with unclipped sun, up to 29 EV range and camera background photos from the location!',
			CREATOR::PBRMATERIALS => 'PBRMaterials.com, founded in 2022, is dedicated to providing high-end scanned and Substance Designer assets for 3D artists.',
			CREATOR::POLIIGON => 'Textures, models and HDRIs for photorealistic 3D rendering. Make better renders, faster. Currently, only the "Free" section is indexed.',
			CREATOR::TEXTURES_COM => 'Take your CG art to the next level with our highest quality content! Currently, only the "Free" section is indexed.',
			CREATOR::CGMOOD => 'CGMood is a fresh, fair 3D marketplace. We are a team of architects and designers with many years of experience in the 3D visualization field. Currently, only the "Free" section is indexed.',
			CREATOR::THREE_D_SCANS => 'A collection of high-quality statues/sculptures scanned in various european museums.',
			CREATOR::LOCATION_TEXTURES => 'Locationtextures.com is an online platform providing high quality royalty-free photo reference packs for games and film industry. We offer free packs and every pack comes with free samples.',
			CREATOR::PBR_PX => 'We are a small team from China, passionate about CG production. Through PBRPX, we provide artists with completely free, unrestricted digital assets, allowing them to unleash their creativity.',
			CREATOR::TWINBRU => 'Twinbru.'
		};
	}

	public function licenseUrl(): string
	{
		return match ($this) {
			CREATOR::AMBIENTCG => 'https://docs.ambientcg.com/license/',
			CREATOR::LOCATION_TEXTURES => 'https://locationtextures.com/privacy-policy/',
			default => ""
		};
	}

	public function baseUrl(): string
	{
		return match ($this) {
			CREATOR::THREE_D_TEXTURES => "https://3dtextures.me",
			CREATOR::AMBIENTCG => 'https://ambientCG.com',
			CREATOR::POLYHAVEN => 'https://polyhaven.com',
			CREATOR::SHARETEXTURES => 'https://sharetextures.com',
			CREATOR::TEXTURECAN => 'https://texturecan.com',
			CREATOR::CGBOOKCASE => 'https://cgbookcase.com',
			CREATOR::NOEMOTIONHDRS => 'http://noemotionhdrs.net',
			CREATOR::GPUOPENMATLIB => 'https://matlib.gpuopen.com/',
			CREATOR::RAWCATALOG => 'https://rawcatalog.com',
			CREATOR::HDRIWORKSHOP => 'https://hdri-workshop.com/',
			CREATOR::PBRMATERIALS => 'https://pbrmaterials.com',
			CREATOR::POLIIGON => 'https://www.poliigon.com/search/free',
			CREATOR::TEXTURES_COM => 'https://www.textures.com/free',
			CREATOR::CGMOOD => 'https://cgmood.com/free',
			CREATOR::THREE_D_SCANS => 'https://threedscans.com/',
			CREATOR::LOCATION_TEXTURES => 'https://locationtextures.com/panoramas/free-panoramas/',
			CREATOR::PBR_PX => 'https://library.pbrpx.com/',
			CREATOR::TWINBRU => 'https://textures.twinbru.com'
		};
	}

	public static function fromSlug(string $slug): ?CREATOR
	{
		foreach (CREATOR::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return null;
	}
}

enum QUIRK: int
{
	case SIGNUP_REQUIRED = 1;
		#case PAYMENT_REQUIRED = 2;
	case ADS = 3;
	case ASSET_PACK = 4;
	case LIMITED_FREE_DOWNLOADS = 5;
	#case LIMITED_FREE_QUALITY = 6;

	public function slug(): string
	{
		return match ($this) {
			QUIRK::SIGNUP_REQUIRED => 'sign-up',
			QUIRK::ADS => 'ads',
			QUIRK::ASSET_PACK => 'asset-pack',
			QUIRK::LIMITED_FREE_DOWNLOADS => 'limited-free-downloads'
		};
	}

	public function name(): string
	{
		return match ($this) {
			QUIRK::SIGNUP_REQUIRED => 'Sign-up Required',
			QUIRK::ADS => 'On-site Ads',
			QUIRK::ASSET_PACK => 'Asset Packs',
			QUIRK::LIMITED_FREE_DOWNLOADS => 'Limited Number of Free Downloads'
		};
	}

	public static function fromSlug(string $slug): QUIRK
	{
		foreach (QUIRK::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
	}
}

enum TYPE: int
{
	case OTHER = 0;
	case PBR_MATERIAL = 1;
	case MODEL_3D = 2;
	case SUBSTANCE_MATERIAL = 3;
	case HDRI = 4;

	public function slug(): string
	{
		return match ($this) {
			TYPE::OTHER => 'other',
			TYPE::PBR_MATERIAL => 'pbr-material',
			TYPE::MODEL_3D => '3d-model',
			TYPE::SUBSTANCE_MATERIAL => 'sbsar',
			TYPE::HDRI => 'hdri',
		};
	}

	public function name(): string
	{
		return match ($this) {
			TYPE::OTHER => 'Other',
			TYPE::PBR_MATERIAL => 'PBR material',
			TYPE::MODEL_3D => '3D model',
			TYPE::SUBSTANCE_MATERIAL => 'Substance material',
			TYPE::HDRI => 'HDRI',
		};
	}

	public static function fromSlug(string $slug): TYPE
	{
		foreach (TYPE::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
	}
}

enum LICENSE: int
{
	case CUSTOM = 0;
	case CC0 = 1;
		#case CC_BY = 2;
		#case CC_BY_SA = 3;
		#case CC_BY_NC = 4;
	case CC_BY_ND = 5;
		#case CC_BY_NC_SA = 6;
		#case CC_BY_NC_ND = 7;
	case APACHE_2_0 = 8;

	public function slug(): string
	{
		return match ($this) {
			LICENSE::CUSTOM => 'custom',
			LICENSE::CC0 => 'cc-0',
			#LICENSE::CC_BY => 'cc-by',
			#LICENSE::CC_BY_SA => 'cc-by-sa',
			#LICENSE::CC_BY_NC => 'cc-by-nc',
			LICENSE::CC_BY_ND => 'cc-by-nd',
			#LICENSE::CC_BY_NC_SA => 'cc-by-nc-sa',
			#LICENSE::CC_BY_NC_ND => 'cc-by-nc-nd',
			LICENSE::APACHE_2_0 => 'apache-2-0',
		};
	}

	public function name(): string
	{
		return match ($this) {
			LICENSE::CUSTOM => 'Custom License - Check Website',
			LICENSE::CC0 => 'Creative Commons CC0',
			#LICENSE::CC_BY => 'Creative Commons BY',
			#LICENSE::CC_BY_SA => 'Creative Commons BY-SA',
			#LICENSE::CC_BY_NC => 'Creative Commons BY-NC',
			LICENSE::CC_BY_ND => 'Creative Commons BY-ND',
			#LICENSE::CC_BY_NC_SA => 'Creative Commons BY-NC-SA',
			#LICENSE::CC_BY_NC_ND => 'Creative Commons BY-NC-ND',
			LICENSE::APACHE_2_0 => 'Apache License 2.0',
		};
	}

	public static function fromSlug(string $slug): LICENSE
	{
		foreach (LICENSE::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
	}
}
