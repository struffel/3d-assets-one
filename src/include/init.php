<?php
	header('Content-type: application/json');
	require_once $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';
	foreach (glob($_SERVER['DOCUMENT_ROOT'] . '/../include/*.php') as $file) {
		require_once $file;
	}

	enum CREATOR : int {
		case AMBIENTCG = 1;
		case POLYHAVEN = 2;
		case SHARETEXTURES = 3;
		case TEXTURECAN = 6;
		case CGBOOKCASE = 5;
		case NOEMOTIONHDRS = 7;
		case CHOCOFUR = 9;
		case GPUOPENMATLIB = 10;
		case RAWCATALOG = 11;
		case HDRIWORKSHOP = 12;
		case PBRMATERIALS = 13;
	
		public function slug(): string {
			return match ($this) {
				CREATOR::AMBIENTCG => 'ambientcg',
				CREATOR::POLYHAVEN => 'polyhaven',
				CREATOR::SHARETEXTURES => 'sharetextures',
				CREATOR::TEXTURECAN => 'texturecan',
				CREATOR::CGBOOKCASE => 'cgbookcase',
				CREATOR::NOEMOTIONHDRS => 'noemotionhdrs',
				CREATOR::CHOCOFUR => 'chocofur',
				CREATOR::GPUOPENMATLIB => 'gpuopen-matlib',
				CREATOR::RAWCATALOG => 'rawcatalog',
				CREATOR::HDRIWORKSHOP => 'hdri-workshop',
				CREATOR::PBRMATERIALS => 'pbrmaterials-com',
			};
		}
	
		public function name(): string {
			return match ($this) {
				CREATOR::AMBIENTCG => 'ambientCG',
				CREATOR::POLYHAVEN => 'Poly Haven',
				CREATOR::SHARETEXTURES => 'Share Textures',
				CREATOR::TEXTURECAN => 'Texture Can',
				CREATOR::CGBOOKCASE => 'CG Bookcase',
				CREATOR::NOEMOTIONHDRS => 'NoEmotion HDRs',
				CREATOR::CHOCOFUR => 'Chocofur Freebies',
				CREATOR::GPUOPENMATLIB => 'AMD GPUOpen MaterialX Library',
				CREATOR::RAWCATALOG => 'Raw Catalog',
				CREATOR::HDRIWORKSHOP => 'hdri workshop',
				CREATOR::PBRMATERIALS => 'PBRMaterials.com',
			};
		}
	
		public function description(): string {
			return match ($this) {
				CREATOR::AMBIENTCG => 'Public Domain materials, HDRIs and models for Physically Based Rendering.',
				CREATOR::POLYHAVEN => 'The Public 3D Asset Library - A combination of the websites "HDRI Haven", "Texture Haven" and "3D Model Haven."',
				CREATOR::SHARETEXTURES => 'ShareTextures.com is creating and sharing PBR textures since 2018.',
				CREATOR::TEXTURECAN => 'Offers free CG textures, free graphics and free patterns for 3D artists.',
				CREATOR::CGBOOKCASE => 'Free PBR textures that come with all the map types needed to create photorealistic materials.',
				CREATOR::NOEMOTIONHDRS => 'An older website with an impressive collection of free HDRIs.',
				CREATOR::CHOCOFUR => 'Improve your Blender 3D projects using thousands of premium quality 3D Blender models and materials! (3dassets.one only lists the free portion of the chocofur store.)',
				CREATOR::GPUOPENMATLIB => 'A collection of high-quality materials and related textures that is available completely for free, hosted by AMD GPUOpen. (Duplicates of materials from Polyhaven are excluded.)',
				CREATOR::RAWCATALOG => 'A unique library that includes many ready-to-use resources for creating amazing projects in the field of video games, films, animation and visualization.',
				CREATOR::HDRIWORKSHOP => 'Royalty free, high quality HDRIs with unclipped sun, up to 29 EV range and camera background photos from the location!',
				CREATOR::PBRMATERIALS => 'PBRMaterials.com, founded in 2022, is dedicated to providing high-end scanned and Substance Designer assets for 3D artists.',
			};
		}
	
		public function baseUrl(): string {
			return match ($this) {
				CREATOR::AMBIENTCG => 'https://ambientCG.com',
				CREATOR::POLYHAVEN => 'https://polyhaven.com',
				CREATOR::SHARETEXTURES => 'https://sharetextures.com',
				CREATOR::TEXTURECAN => 'https://texturecan.com',
				CREATOR::CGBOOKCASE => 'https://cgbookcase.com',
				CREATOR::NOEMOTIONHDRS => 'http://noemotionhdrs.net',
				CREATOR::CHOCOFUR => 'https://store.chocofur.com/search/free',
				CREATOR::GPUOPENMATLIB => 'https://matlib.gpuopen.com/',
				CREATOR::RAWCATALOG => 'https://rawcatalog.com',
				CREATOR::HDRIWORKSHOP => 'https://hdri-workshop.com/',
				CREATOR::PBRMATERIALS => 'https://pbrmaterials.com',
			};
		}

		public static function fromSlug(string $slug) : CREATOR {
			foreach (CREATOR::cases() as $c) {
				if($c->slug() === $slug){
					return $c;
				}
			}
		}

	}

	enum TYPE : int {
		case OTHER = 0;
		case PBR_MATERIAL = 1;
		case MODEL_3D = 2;
		case SUBSTANCE_MATERIAL = 3;
		case HDRI = 4;
		case BRUSH = 5;
	
		public function slug(): string {
			return match ($this) {
				TYPE::OTHER => 'other',
				TYPE::PBR_MATERIAL => 'pbr-material',
				TYPE::MODEL_3D => '3d-model',
				TYPE::SUBSTANCE_MATERIAL => 'sbsar',
				TYPE::HDRI => 'hdri',
				TYPE::BRUSH => 'brush',
			};
		}
	
		public function name(): string {
			return match ($this) {
				TYPE::OTHER => 'Other',
				TYPE::PBR_MATERIAL => 'PBR material',
				TYPE::MODEL_3D => '3D model',
				TYPE::SUBSTANCE_MATERIAL => 'Substance material',
				TYPE::HDRI => 'HDRI',
				TYPE::BRUSH => 'Brush',
			};
		}
	}

	enum LICENSE : int {
		case CUSTOM = 0;
		case CC0 = 1;
		case CC_BY = 2;
		case CC_BY_SA = 3;
		case CC_BY_NC = 4;
		case CC_BY_ND = 5;
		case CC_BY_NC_SA = 6;
		case CC_BY_NC_ND = 7;
		case APACHE_2_0 = 8;
	
		public function slug(): string {
			return match ($this) {
				LICENSE::CUSTOM => 'custom',
				LICENSE::CC0 => 'cc-0',
				LICENSE::CC_BY => 'cc-by',
				LICENSE::CC_BY_SA => 'cc-by-sa',
				LICENSE::CC_BY_NC => 'cc-by-nc',
				LICENSE::CC_BY_ND => 'cc-by-nd',
				LICENSE::CC_BY_NC_SA => 'cc-by-nc-sa',
				LICENSE::CC_BY_NC_ND => 'cc-by-nc-nd',
				LICENSE::APACHE_2_0 => 'apache-2-0',
			};
		}
	
		public function name(): string {
			return match ($this) {
				LICENSE::CUSTOM => 'Custom Free License - Check Website',
				LICENSE::CC0 => 'Creative Commons CC0',
				LICENSE::CC_BY => 'Creative Commons BY',
				LICENSE::CC_BY_SA => 'Creative Commons BY-SA',
				LICENSE::CC_BY_NC => 'Creative Commons BY-NC',
				LICENSE::CC_BY_ND => 'Creative Commons BY-ND',
				LICENSE::CC_BY_NC_SA => 'Creative Commons BY-NC-SA',
				LICENSE::CC_BY_NC_ND => 'Creative Commons BY-NC-ND',
				LICENSE::APACHE_2_0 => 'Apache License 2.0',
			};
		}
	}
	
	enum SortingOrder: string{
		case LATEST = "latest";
		case OLDEST = "oldest";
		case RANDOM = "random";
	}

	class Asset{
		public function __construct(
			public ?string $id = NULL,
			public ?string $name = NULL,
			public ?string $url = NULL,
			public ?string $date = NULL,
			public ?array $tags = NULL,
			public ?TYPE $type = NULL,
			public ?LICENSE $license = NULL,
			public ?CREATOR $creator = NULL,

			public bool $active = false,
			public string $thumbnailUrl
		){}
	}
	
	abstract class CreatorFetcher{

		// class variables
		private CREATOR $creator;
		private array $config;

		// final functions
		public final function __construct(){
			
			if(!$this->creator){
				throw new Exception("Creator ID not set.", 1);
			}

			$this->config = json_decode(file_get_contents($this->creator->value.".json"),true);
		}

		private final function getExistingUrls() : array{

			if(!$this->creator){
				throw new Exception("Creator not set.", 1);
			}

			$query = new AssetQuery();
			$query->filterCreator = [$this->creator];
			$query->filterActive = NULL;
			$result = DatabaseLogic::getAssets($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}
			return $existingUrls;
		}

		// existing, but editable functions
		public function postProcessThumbnail(string $imageBlob):string {
			return $imageBlob;
		}
		
		// abstract functions
		public abstract function findNewAssets():AssetCollection;
	}
	
	class AssetCollection{
		public function __construct(
			public array $assets = array(),
			public int $totalNumberOfAssetsInBackend,
			public AssetQuery $nextCollection
		){}
	}

	class AssetQuery{
		public function __construct(
		// Basics
		public int $offset = 0,
		public int $limit = 100,
		public SortingOrder $sort = SortingOrder::LATEST,

		// Filters
		public ?array $filterAssetId = NULL,
		public ?array $filterTag = NULL,
		public ?array $filterCreator = NULL,		// CREATOR
		public ?array $filterLicense = NULL,		// LICENSE
		public ?array $filterType = NULL,			// TYPE
		public ?int $filterActive = 1,			// 0: Inactive, 1: Active, -1: Disabled, NULL: Any

		){}
		
	}
?>