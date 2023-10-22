<?php

enum SortingOrder: string{
	case LATEST = "latest";
	case OLDEST = "oldest";
	case RANDOM = "random";
}

class Asset{
	public function __construct(
		public ?int $id = NULL,
		public string $name = NULL,
		public string $url = NULL,
		public string $thumbnailUrl,
		public string $date = date("Y-m-d"),
		public array $tags = [],
		public TYPE $type = NULL,
		public LICENSE $license = NULL,
		public CREATOR $creator = NULL,
		public bool $active = false
	){}
}

class AssetCollection{
	public function __construct(
		public array $assets = array(),
		public ?int $totalNumberOfAssetsInBackend = NULL,
		public ?AssetQuery $nextCollection = NULL
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
	public ?int $filterActive = 1,				// 0: Inactive, 1: Active, -1: Disabled, NULL: Any

	){}
	
}

class AssetLogic{
	public static function writeAssetCollection(AssetCollection $newAssetCollection){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Writing asset collection to DB");
		foreach ($newAssetCollection->assets as $a) {
			AssetLogic::writeAssetToDatabase($a);
		}
		LogLogic::write("Finished writing asset collection to DB");
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function activateAssetCollection(AssetCollection $assetCollection){
		foreach ($assetCollection->assets as $a) {
			AssetLogic::activateAsset($a);
		}
	}

	public static function filterTagArray(array $inputArray) {
		// Initialize an empty result array
		$resultArray = array();
	
		// Loop through each element in the input array
		foreach ($inputArray as $element) {
			// Trim the element and convert it to lowercase
			$filteredElement = strtolower(trim($element));
	
			// Split the element into multiple elements by space
			$splitElements = preg_split('/\s+/', $filteredElement);
	
			// Loop through the split elements and remove non-alphanumeric characters
			foreach ($splitElements as $splitElement) {
				// Remove non-alphanumeric characters using a regular expression
				$filteredSplitElement = preg_replace('/[^a-z0-9]/', '', $splitElement);
	
				// Check if the filtered element is not empty and add it to the result array
				if (!empty($filteredSplitElement)) {
					$resultArray[] = $filteredSplitElement;
				}
			}
		}
	
		return array_unique($resultArray);
	}

	public static function activateAsset(Asset $asset){
		DatabaseLogic::runQuery("UPDATE Asset SET assetActive = '1' WHERE assetId = ?",[$asset->id]);
	}

	public static function getUrlFromAssetId(string $assetId) : string{
		$sql = "SELECT assetUrl FROM Asset WHERE assetId = ? LIMIT 1;";
		$sqlParameters = [intval($assetId)];
		$sqlResult = DatabaseLogic::runQuery($sql,$sqlParameters);
		
		$row = $sqlResult->fetch_assoc();
		return $row['AssetUrl'];
	}

	public static function addAssetClickByAssetId(int $assetId){
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		$sqlParameters = [intval($assetId)];
		DatabaseLogic::runQuery($sql,$sqlParameters);
	}

	public static function writeAssetToDatabase(Asset $newAsset){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Inserting Asset: ".$newAsset->url);

		// Base Asset
		$sql = "INSERT INTO Asset (assetId, assetName, assetUrl, assetThumbnailUrl, assetDate, assetClicks, licenseId, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?);";
		$parameters = [$newAsset->name, $newAsset->url,$newAsset->thumbnailUrl,$newAsset->date, 0 ,$newAsset->license->value,$newAsset->type->value,$newAsset->creator->value];
		$result = DatabaseLogic::runQuery($sql,$parameters);

		// Tags
		foreach ($newAsset->tags as $tag) {
			$sql = "INSERT INTO Tag (assetId,tagName) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
			$parameters = [$newAsset->url,$tag];
			DatabaseLogic::runQuery($sql,$parameters);
		}
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function getAssets(AssetQuery $query): AssetCollection{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Loading assets based on query: ".var_export($query, true));

		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT assetId,assetUrl,assetThumbnailUrl,assetName,assetActive,assetDate,assetClicks,licenseId,typeId,creatorId,group_concat(tagName,',') as assetTags FROM ";
		$sqlValues = [];

		// Tag filter

		$sqlCommand .= " ( SELECT assetId FROM Tag ";
		foreach ($query->filterTag as $tag) {
			$sqlCommand .= " INTERSECT SELECT assetId FROM Tag WHERE tagName=? ";
			$sqlValues []= $tag;
		}
		$sqlCommand .= " ) LEFT JOIN Asset USING (assetId) LEFT JOIN Tag USING (assetId) WHERE TRUE ";

		if($query->filterAssetId){
			$sqlCommand .= " AND assetId IN (?) ";
			$sqlValues []= $query->filterAssetId;
		}

		if($query->filterCreator){
			$sqlCommand .= " AND creatorId IN (?) ";
			$sqlValues []= $query->filterCreator;
		}

		if($query->filterLicense){
			$sqlCommand .= " AND licenseId IN (?) ";
			$sqlValues []= $query->filterLicense;
		}

		if($query->filterType){
			$sqlCommand .= " AND licenseId IN (?) ";
			$sqlValues []= $query->filterType;
		}

		if($query->filterActive){
			$sqlCommand .= " AND assetActive=? ";
			$sqlValues []= $query->filterActive;
		}

		// Sort
		$sqlCommand .= match ($query->sort) {
			SortingOrder::LATEST => " ORDER BY assetDate DESC ",
			SortingOrder::OLDEST => " ORDER BY assetDate ASC ",
			SortingOrder::RANDOM => " ORDER BY RANDOM() "
		};

		// Offset and Limit
		$sqlCommand .= " LIMIT ? OFFSET ? ";
		$sqlValues []= $query->offset;
		$sqlValues []=$query->limit;


		// Fetch data from DB
		$databaseOutput = DatabaseLogic::runQuery($sqlCommand,$sqlValues);
		$databaseOutputFoundRows = DatabaseLogic::runQuery("SELECT FOUND_ROWS() as RowCount;");

		// Prepare the final asset collection
		$nextCollectionQuery = $query;
		$nextCollectionQuery->offset += $nextCollectionQuery->limit;
		$output = new AssetCollection(
			totalNumberOfAssetsInBackend: $databaseOutputFoundRows->fetch_assoc()['RowCount'],
			nextCollection: $nextCollectionQuery
		);
		
		// Assemble the asset objects
		while ($row = $databaseOutput->fetch_assoc()) {
			$output->assets []= new Asset(
				active: $row['assetAcive'],
				thumbnailUrl: $row['thumbnailUrl'],
				id: $row['assetId'],
				name: $row['assetName'],
				url: $row['assetUrl'],
				date: $row['assetDate'],
				tags: $row['assetTags'],
				type: TYPE::from($row['typeId']),
				license: LICENSE::from($row['licenseId']),
				creator: CREATOR::from($row['creatorId'])
			);
		}

		LogLogic::stepOut(__FUNCTION__);
		return $output;
	}
}