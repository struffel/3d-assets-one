<?php

enum SORTING: string{
	case POPULAR = "popular";
	case LATEST = "latest";
	case OLDEST = "oldest";
	case RANDOM = "random";
	case MOST_CLICKED = "most-clicked";
	case LEAST_CLICKED = "least-clicked";
	case MOST_TAGGED = "most-tagged";
	case LEAST_TAGGED = "least-tagged";

	/**
	 * Returns the enum value for the string. 
	 * Every other/invalid string gets turned into SORTING::LATEST.
	 */
	public static function fromAnyString(string $string) : SORTING{
		if(in_array($string,array_column(SORTING::cases(), 'value'))){
			return SORTING::from($string);
		}else{
			return SORTING::LATEST;
		}
	}
}

enum ASSET_STATUS: int {

	/**
	 * The asset is not active and should likely never be activated.
	 * This may be, for example, because the asset fetching function for that creator
	 * erroneously detects a certain page as an asset and keeping it in the DB is easier
	 * than adding all relevant edge cases to the fetching function.
	 */
	case BLOCKED = -1;

	/**
	 * The asset is not active and awaits activation.
	 * This happens with a freshly registered asset that has not yet had its thumbnail processed.
	 */
	case INACTIVE = 0;

	/**
	 * The asset is active and can be found in regular searches.
	 */
	case ACTIVE = 1;
}

/**
 * The main asset class.
 * It represents one PBR material, 3D model or other asset.
 */
class Asset{
	public function __construct(
		public ?int $id,
		public string $name,
		public string $url,
		public string $thumbnailUrl,
		public string $date,
		public array $tags = [],
		public TYPE $type,
		public LICENSE $license,
		public CREATOR $creator,
		public array $quirks = [],	// Array of QUIRK
		public ASSET_STATUS $status = ASSET_STATUS::INACTIVE
	){}
}

/**
 * A collection of `Asset`s.
 * It is used for pagination.
 */
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
	public ?int $offset = NULL,						// ?offset
	public ?int $limit = NULL,								// ?limit
	public ?SORTING $sort = SORTING::LATEST,		// ?sort

	// Filters
	public ?array $filterAssetId = [],		// ?id, Allows filtering for specific asset ids.
	public ?array $filterTag = [],			// ?tags, Assets must have ALL tags in the array in order to be included.
	public ?array $filterCreator = [],		// ?creator, limits the search to certain creators.
	public ?array $filterLicense = [],		// ?license, defines which licenes should be allowed. Empty array causes all licenses to be allowed.
	public ?array $filterType = [],			// ?type, defines which types of asset should be included. Empty array causes all types to be included.
	public ?array $filterAvoidQuirk = [],		// ?avoid, defines which quirks a site MUST NOT have to still be included. Empty array causes all quirks to be allowed.
	public ?ASSET_STATUS $filterStatus = ASSET_STATUS::ACTIVE,				// NULL => Any status

	){}

	public function toHttpGet(bool $includeStatus = false) : string{

		$enumToSlugConverter = function($e){return $e->slug();};

		$output = [];
		
		$output['q'] = implode(",",$this->filterTag);
		$output['offset'] = $this->offset;
		$output['limit'] = $this->limit;
		$output['sort'] = $this->sort->value;
		$output['id'] = $this->filterAssetId;
		$output['creator'] = array_map($enumToSlugConverter,$this->filterCreator);
		$output['license'] = array_map($enumToSlugConverter,$this->filterLicense);
		$output['type'] = array_map($enumToSlugConverter,$this->filterType);
		$output['avoid'] = array_map($enumToSlugConverter,$this->filterAvoidQuirk);

		if($includeStatus){
			$output['status'] = $this->filterStatus->value ?? NULL;
		}

		return http_build_query($output);
	}

	/**
	 * Generates a new AssetQuery based on the current HTTP GET parameters in $_GET.
	 * The asset status can be forced to a specific value using the method paramter 'filterStatus'.
	 * Setting filterStatus to NULL allows the status to be controlled using a HTTP parameter.
	 */
	public static function fromHttpGet(?ASSET_STATUS $filterStatus = ASSET_STATUS::ACTIVE) : AssetQuery{

		// status filter (only if it's not defined in the method head)
		if($filterStatus === NULL && isset($_GET['status']) && $_GET['status'] != "" ){
			$filterStatus = ASSET_STATUS::tryFrom(intval($_GET['status']));
		}

		// assetId filter
		$filterAssetId = [];
		foreach($_GET['id'] ?? [] as $assetId) {
			$filterAssetId []= intval($assetId);
		}
		$filterAssetId = array_filter($filterAssetId);

		// creator filter
		$filterCreator = [];
		foreach($_GET['creator'] ?? [] as $creatorSlug){
			$filterCreator []= CREATOR::fromSlug($creatorSlug);
		}
		$filterCreator = array_filter($filterCreator);

		// type filter
		$filterType = [];
		foreach($_GET['type'] ?? [] as $typeSlug){
			$filterType []= TYPE::fromSlug($typeSlug);
		}
		$filterType = array_filter($filterType);

		// license filter
		$filterLicense = [];
		foreach($_GET['license'] ?? [] as $licenseSlug){
			$filterLicense []= LICENSE::fromSlug($licenseSlug);
		}
		$filterLicense = array_filter($filterLicense);

		// quirk filter
		$filterAvoidQuirk = [];
		foreach($_GET['avoid'] ?? [] as $quirkSlug){
			$filterAvoidQuirk []= QUIRK::fromSlug($quirkSlug);
		}
		$filterAvoidQuirk = array_filter($filterAvoidQuirk);

		return new AssetQuery(
			offset: intval($_GET['offset'] ?? 0),
			limit: min(intval($_GET['limit'] ?? 150),500),
			sort: SORTING::fromAnyString($_GET['sort'] ?? "latest"),
			filterAssetId: $filterAssetId,
			filterTag: array_map('trim',array_filter(preg_split('/\s|,/',$_GET['q'] ?? ""))),
			filterCreator: $filterCreator,
			filterLicense: $filterLicense,
			filterType: $filterType,
			filterAvoidQuirk: $filterAvoidQuirk,
			filterStatus: $filterStatus
		);

	}

}

class AssetLogic{

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

	public static function getUrlById(string $assetId) : string{
		$sql = "SELECT assetUrl FROM Asset WHERE assetId = ? LIMIT 1;";
		$sqlResult = DatabaseLogic::runQuery($sql,[intval($assetId)]);
		
		$row = $sqlResult->fetch_assoc();
		return $row['assetUrl'];
	}

	public static function addAssetClickById(int $assetId){
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		DatabaseLogic::runQuery($sql,[intval($assetId)]);
	}

	public static function saveAssetToDatabase(Asset $asset) {

		LogLogic::stepIn(__FUNCTION__);

		if($asset->id){
			LogLogic::write("Updating Asset with id: ".$asset->id);

			// Base Asset
			$sql = "UPDATE Asset SET assetName=?,assetActive=?,assetUrl=?,assetThumbnailUrl=?,assetDate=?,licenseId=?,typeId=?,creatorId=? WHERE assetId = ?";
			$parameters = [$asset->name,$asset->status->value,$asset->url,$asset->thumbnailUrl,$asset->date,$asset->license->value,$asset->type->value,$asset->creator->value,$asset->id];
			DatabaseLogic::runQuery($sql,$parameters);

			// Tags
			DatabaseLogic::runQuery("DELETE FROM Tag WHERE assetId = ?",[$asset->id]);
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES (?,?);";
				$parameters = [$asset->id,$tag];
				DatabaseLogic::runQuery($sql,$parameters);
			}

			// Quirks

			DatabaseLogic::runQuery("DELETE FROM Quirk WHERE assetId = ?",[$asset->id]);
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES (?,?);";
				$parameters = [$asset->id,$quirk->value];
				DatabaseLogic::runQuery($sql,$parameters);
			}

		}else{
			LogLogic::write("Inserting new asset with url:".$asset->url);

			// Base Asset
			$sql = "INSERT INTO Asset (assetId, assetActive,assetName, assetUrl, assetThumbnailUrl, assetDate, assetClicks, licenseId, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?);";
			$parameters = [$asset->name, $asset->status->value, $asset->url,$asset->thumbnailUrl,$asset->date, 0 ,$asset->license->value,$asset->type->value,$asset->creator->value];
			DatabaseLogic::runQuery($sql,$parameters);

			// Tags
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url,$tag];
				DatabaseLogic::runQuery($sql,$parameters);
			}
	
			// Quirks
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url,$quirk->value];
				DatabaseLogic::runQuery($sql,$parameters);
			}
		}
		
		LogLogic::stepOut(__FUNCTION__);
		return $asset;
		
	}

	public static function getAssets(AssetQuery $query): AssetCollection{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Loading assets based on query: ".var_export($query, true));

		

		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT SQL_CALC_FOUND_ROWS assetId,assetUrl,assetThumbnailUrl,assetName,assetActive,assetDate,assetClicks,licenseId,typeId,creatorId,assetTags,quirkIds FROM Asset ";
		$sqlValues = [];

		// Joins

		$sqlCommand .= " LEFT JOIN (SELECT assetId, GROUP_CONCAT(tagName SEPARATOR ',') AS assetTags FROM Tag GROUP BY assetId ) AllTags USING (assetId) ";
		$sqlCommand .= " LEFT JOIN (SELECT assetId, GROUP_CONCAT(quirkId SEPARATOR ',') AS quirkIds FROM Quirk GROUP BY assetId ) AllQuirks USING (assetId) ";

		$sqlCommand .= " WHERE TRUE ";


		foreach($query->filterTag as $tag){
			$sqlCommand .= " AND assetId IN (SELECT assetId FROM Tag WHERE tagName = ? ) ";
			$sqlValues []= $tag;
		}
		
		foreach($query->filterAvoidQuirk as $quirk){
			$sqlCommand .= " AND assetId NOT IN (SELECT assetId FROM Quirk WHERE quirkId = ? ) ";
			$sqlValues []= $quirk->value;
		}
		

		if(sizeof($query->filterAssetId) > 0){
			$ph = DatabaseLogic::generatePlaceholder($query->filterAssetId);
			$sqlCommand .= " AND assetId IN ($ph) ";
			$sqlValues = array_merge($sqlValues,$query->filterAssetId);
		}

		if(sizeof($query->filterType) > 0){
			$ph = DatabaseLogic::generatePlaceholder($query->filterType);
			$sqlCommand .= " AND typeId IN ($ph) ";
			$sqlValues = array_merge($sqlValues,$query->filterType);
		}

		if(sizeof($query->filterLicense) > 0){
			$ph = DatabaseLogic::generatePlaceholder($query->filterLicense);
			$sqlCommand .= " AND licenseId IN ($ph) ";
			$sqlValues = array_merge($sqlValues,$query->filterLicense);
		}

		if(sizeof($query->filterCreator) > 0){
			$ph = DatabaseLogic::generatePlaceholder($query->filterCreator);
			$sqlCommand .= " AND creatorId IN ($ph) ";
			$sqlValues = array_merge($sqlValues,$query->filterCreator);
		}

		if($query->filterStatus !== NULL){
			$sqlCommand .= " AND assetActive=? ";
			$sqlValues []= $query->filterStatus;
		}

		// Sort
		$sqlCommand .= match ($query->sort) {

			// Options for public display
			SORTING::LATEST => " ORDER BY assetDate DESC, assetId DESC ",
			SORTING::OLDEST => " ORDER BY assetDate ASC, assetId DESC ",
			SORTING::RANDOM => " ORDER BY RAND() ",
			SORTING::POPULAR => " ORDER BY ( (assetClicks + 10) / POW( ABS( DATEDIFF( NOW(),assetDate ) ) + 1 , 1.3 ) ) DESC, assetDate DESC, assetId DESC ",

			// Options for internal editor (potentially less optimized)
			SORTING::LEAST_CLICKED => " ORDER BY assetClicks ASC ",
			SORTING::MOST_CLICKED => " ORDER BY assetClicks DESC ",
			SORTING::LEAST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.assetId = Asset.assetId) ASC ",
			SORTING::MOST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.assetId = Asset.assetId) DESC ",
		};

		// Offset and Limit
		if($query->limit != NULL){
			// Clean up query
			$query->limit = max(1,$query->limit);
			$query->offset = max(0,$query->offset);

			$sqlCommand .= " LIMIT ? OFFSET ? ";
			$sqlValues []=$query->limit;
			$sqlValues []=$query->offset;
		}
		
		
		// Fetch data from DB
		$databaseOutput = DatabaseLogic::runQuery($sqlCommand,$sqlValues);
		$databaseOutputFoundRows = DatabaseLogic::runQuery("SELECT FOUND_ROWS() as RowCount;");

		// Prepare the final asset collection
		$output = new AssetCollection(
			totalNumberOfAssetsInBackend: $databaseOutputFoundRows->fetch_assoc()['RowCount']
		);

		// Add a query for more assets, if there are any 
		if($output->totalNumberOfAssetsInBackend > $query->offset + $query->limit){
			$nextCollectionQuery = clone $query;
			$nextCollectionQuery->offset += $nextCollectionQuery->limit;
			$output->nextCollection = $nextCollectionQuery;
		}
		
		
		// Assemble the asset objects
		while ($row = $databaseOutput->fetch_assoc()) {

			$quirks = [];
			foreach (array_filter(explode(",",$row['quirkIds'] ?? "")) as $q) {
				$quirks []= QUIRK::from(intval($q));
			}

			$tags = array_filter(explode(',',$row['assetTags'] ?? ""));
			
			$output->assets []= new Asset(
				status: ASSET_STATUS::from($row['assetActive']),
				thumbnailUrl: $row['assetThumbnailUrl'],
				id: $row['assetId'],
				name: $row['assetName'],
				url: $row['assetUrl'],
				date: $row['assetDate'],
				tags: $tags,
				type: TYPE::from($row['typeId']),
				license: LICENSE::from($row['licenseId']),
				creator: CREATOR::from($row['creatorId']),
				quirks: $quirks
			);
			
		}

		LogLogic::stepOut(__FUNCTION__);
		return $output;
	}
}