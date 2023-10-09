<?php
	header('Content-type: application/json');
	require_once $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';
	foreach (glob($_SERVER['DOCUMENT_ROOT'] . '/../include/*.php') as $file) {
		require_once $file;
	}

	class Asset{
		public function __construct(
			public ?string $assetId = NULL,
			public ?string $assetName = NULL,
			public ?string $url = NULL,
			public ?string $date = NULL,
			public ?array $tags = NULL,
			public ?Type $type = NULL,
			public ?License $license = NULL,
			public ?Creator $creator = NULL,

			public bool $active = false,
			public string $thumbnailUrl
		){}
	}

	class Type{
		public function __construct(
			public int $typeId,
			public ?string $typeSlug = NULL,
			public ?string $typeName = NULL
		){}
	}

	class License{
		public function __construct(
			public int $licenseId,
			public ?string $licenseSlug = NULL,
			public ?string $licenseName = NULL
		){}
	}

	class Creator{
		public function __construct(
			public int $creatorId,
			public ?string $creatorSlug = NULL,
			public ?string $creatorName = NULL
		){}
	}
	
	abstract class CreatorFetcher{

		public function __construct(){
			
			if(!$this->creatorId){
				throw new Exception("Creator ID not set.", 1);
			}

			$this->config = json_decode(file_get_contents($this->creatorId.".json"),true);
		}

		private function getExistingUrls() : array{

			if(!$this->creatorId){
				throw new Exception("Creator ID not set.", 1);
			}

			$query = new AssetQuery();
			$query->filterCreatorId = [$this->creatorId];
			$query->filterActive = NULL;
			$result = DatabaseLogic::getAssets($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}
			return $existingUrls;
		}

		// class variables
		private abstract int $creatorId;
		private array $config;
		
		// abstract functions for every creator
		public abstract function findNewAssets():AssetCollection;
		public abstract function postProcessThumbnail(string $imageBlob):string;
	}
	
	class AssetCollection{
		public function __construct(
			public array $assets = array(),
			public int $totalNumberOfAssetsInBackend,
			public AssetQuery $nextCollection
		){}
	}

	enum SortingOrder: string{
		case LATEST = "latest";
		case OLDEST = "oldest";
		case RANDOM = "random";
	}

	class AssetQuery{
		public function __construct(
		// Basics
		public int $offset = 0,
		public int $limit = 0,
		public SortingOrder $sort = SortingOrder::LATEST,

		// Inclusions
		public bool $includeTag = false,
		public bool $includeCreator = false,
		public bool $includeLicense = false,
		public bool $includeType = false,
		public bool $includeInternal = false,

		// Filters
		public ?array $filterAssetId = NULL,
		public ?array $filterTag = NULL,
		public ?array $filterCreatorSlug = NULL,
		public ?array $filterCreatorId = NULL,
		public ?array $filterLicenseSlug = NULL,
		public ?array $filterTypeSlug = NULL,
		public ?bool $filterActive = true,

		){}
		
	}
?>