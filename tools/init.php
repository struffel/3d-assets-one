<?php
	header('Content-type: application/json');
	require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

	class Asset{
		public ?string $assetId;
		public ?string $assetName;
		public ?string $url;
		public ?string $date;
		public ?array $tags;
		public ?Type $type;
		public ?License $license;
		public ?CreatorData $creator;

		public bool $active;
		public string $thumbnailUrl;
	}

	class Type{
		public ?string $typeId;
		public ?string $typeSlug;
		public ?string $typeName;
	}

	class License{
		public ?string $licenseId;
		public ?string $licenseSlug;
		public ?string $licenseName;
	}

	class CreatorData{
		public ?string $creatorId;
		public ?string $creatorSlug;
		public ?string $creatorName;
	}
	
	abstract class CreatorInterface{
		abstract function findNewAssets():AssetCollection;
		abstract function refreshAssetById(int $assetId):Asset;
	}
	
	class AssetCollection{
		public array $assets = array();
		public string $totalNumberOfAssets;
		//public SearchQuery $previousPage;
		public AssetQuery $nextCollection;
	}

	class AssetFilter{
		public ?array $assetId = NULL;
		public ?array $tag = NULL;
		public ?array $creatorSlug = NULL;
		public ?array $creatorId = NULL;
		public ?array $licenseSlug = NULL;
		public ?array $typeSlug = NULL;
		public ?bool $active = true;
	}

	class AssetInclusion{
		public bool $asset = true;
		public bool $tag = false;
		public bool $creator = false;
		public bool $license = false;
		public bool $type = false;
		public bool $internal = false;
	}

	class AssetQuery{
		public int $offset;
		public int $limit;
		public string $sort = "latest";
		public AssetFilter $filter;
		public AssetInclusion $include;
		
		function __construct(){
			$this->filter = new AssetFilter();
			$this->include = new AssetInclusion();
		}
	}
?>