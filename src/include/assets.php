<?php

use Illuminate\Cache\DatabaseLock;

enum SORTING: string{
	case LATEST = "latest";
	case OLDEST = "oldest";
	case RANDOM = "random";

	public static function fromString(string $string) : SORTING{
		return match ($string) {
			"latest" => SORTING::LATEST,
			"oldest" => SORTING::OLDEST,
			"random" => SORTING::RANDOM,
			default => SORTING::LATEST
		};
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
		public ASSET_STATUS = ASSET_STATUS::INACTIVE
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
	public SORTING $sort = SORTING::LATEST,

	// Filters
	public ?array $filterAssetId = NULL,		// Allows filtering for specific asset ids.
	public ?array $filterTag = NULL,			// Assets must have ALL tags in the array in order to be included.
	public ?array $filterCreator = NULL,		// CREATOR, limits the search to certain creators.
	public ?array $filterLicense = NULL,		// LICENSE, defines which licenes should be allowed. Empty array causes all licenses to be allowed.
	public ?array $filterType = NULL,			// TYPE, defines which types of asset should be included. Empty array causes all types to be included.
	public ?array $filterAvoidQuirk = NULL,		// QUIRK, defines which quirks a site MUST NOT have to still be included. Empty array causes all quirks to be allowed.
	public ?ASSET_STATUS $filterActive = ASSET_STATUS::ACTIVE,				// NULL => Any status

	){}

	/**
	 * Generates a new AssetQuery based on the current HTTP GET parameters in $_GET.
	 */
	public static function fromHttpGet(int $filterActive = 1) : AssetQuery{

		// assetId filter
		$filterAssetId = [];
		foreach(StringLogic::explodeFilterTrim(",",$_GET['id'] ?? "") as $assetId) {
			$filterAssetId []= intval($assetId);
		}

		// creator filter
		$filterCreator = [];
		foreach(StringLogic::explodeFilterTrim(",",$_GET['creator'] ?? "") as $creatorSlug){
			$filterCreator []= CREATOR::fromSlug($creatorSlug);
		}

		// type filter
		$filterType = [];
		foreach(StringLogic::explodeFilterTrim(",",$_GET['type'] ?? "") as $typeSlug){
			$filterType []= TYPE::fromSlug($typeSlug);
		}
		// license filter
		$filterLicense = [];
		foreach(StringLogic::explodeFilterTrim(",",$_GET['license'] ?? "") as $licenseSlug){
			$filterLicense []= LICENSE::fromSlug($licenseSlug);
		}

		// quirk filter
		$filterAvoidQuirk = [];
		foreach(StringLogic::explodeFilterTrim(","$_GET['avoid'] ?? "") as $quirkSlug){
			$filterAvoidQuirk []= QUIRK::fromSlug($quirkSlug);
		}

		return new AssetQuery(
			offset: intval($_GET['offset'] ?? 0),
			limit: intval($_GET['limit'] ?? 100),
			sort: SORTING::fromString($_GET['sort'] ?? "latest"),
			filterAssetId: $filterAssetId,
			filterTag: array_map('trim',array_filter(preg_split('/\s|,/',$_GET['q'] ?? ""))),
			filterCreator: $filterCreator,
			filterLicense: $filterLicense,
			filterType: $filterType,
			filterAvoidQuirk: $filterAvoidQuirk,
			filterActive: $filterActive
		);

	}

}

class AssetLogic{
	/**
	 * Writes all Assets in an AssetCollection to the database.
	 */
	public static function writeAssetCollectionToDatabase(AssetCollection $newAssetCollection){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Writing asset collection to DB");
		foreach ($newAssetCollection->assets as $a) {
			AssetLogic::writeAssetToDatabase($a);
		}
		LogLogic::write("Finished writing asset collection to DB");
		LogLogic::stepOut(__FUNCTION__);
	}

	/**
	 * Activates all assets in an AssetCollection.
	 */
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

	/**
	 * Activates an asset in the database.
	 * This causes it to show up in regular asset searches.
	 */
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

		/*

		SQL statement reference:

		SELECT SQL_CALC_FOUND_ROWS 
			assetId,
			assetUrl,
			assetThumbnailUrl,
			assetName,
			assetActive,
			assetDate,
			assetClicks,
			licenseId,
			typeId,
			creatorId,
			GROUP_CONCAT(tagName SEPARATOR ',') as tags,
			GROUP_CONCAT(quirkId SEPARATOR ',') as quirkIds,
			assetClicks/POW(ABS(DATEDIFF(NOW(),assetDate))+1,1.25) as popularityScore
		FROM
			(
				SELECT * FROM Asset
				WHERE TRUE
				AND creatorId IN (?)
				AND typeId IN (?)
				AND assetId IN (?)
				AND licenseId IN (?)
				AND assetActive=?
			) AssetPrefiltered
			LEFT JOIN Tag USING (assetId)
			LEFT JOIN Quirk USING (assetId)
		GROUP BY assetId
		ORDER BY ?
		HAVING TRUE
		AND FIND_IN_SET(?,tags)
		AND NOT FIND_IN_SET(?,quirkIds)

		*/

		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT SQL_CALC_FOUND_ROWS assetId,assetUrl,assetThumbnailUrl,assetName,assetActive,assetDate,assetClicks,licenseId,typeId,creatorId,GROUP_CONCAT(tagName SEPARATOR ',') as assetTags FROM ";
		$sqlValues = [];

		// Tag filter

		$sqlCommand .= " ( SELECT DISTINCT assetId FROM Tag WHERE TRUE ";
		foreach ($query->filterTag as $tag) {
			$sqlCommand .= " AND assetId IN ( SELECT assetId FROM Tag WHERE tagName=?) ";
			$sqlValues []= $tag;
		}
		$sqlCommand .= " ) TagResults LEFT JOIN Asset USING (assetId) LEFT JOIN Tag USING (assetId) WHERE TRUE ";

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

		if(isset($query->filterActive)){
			$sqlCommand .= " AND assetActive=? ";
			$sqlValues []= $query->filterActive;
		}

		$sqlCommand .= " GROUP BY assetId ";

		// Sort
		$sqlCommand .= match ($query->sort) {
			SORTING::LATEST => " ORDER BY assetDate DESC ",
			SORTING::OLDEST => " ORDER BY assetDate ASC ",
			SORTING::RANDOM => " ORDER BY RANDOM() "
		};

		// Offset and Limit
		$sqlCommand .= " LIMIT ? OFFSET ? ";
		$sqlValues []=$query->limit;
		$sqlValues []=$query->offset;
		
		// Fetch data from DB
		$databaseOutput = DatabaseLogic::runQuery($sqlCommand,$sqlValues);
		$databaseOutputFoundRows = DatabaseLogic::runQuery("SELECT FOUND_ROWS() as RowCount;");

		// Prepare the final asset collection
		$nextCollectionQuery = clone $query;
		$nextCollectionQuery->offset += $nextCollectionQuery->limit;
		$output = new AssetCollection(
			totalNumberOfAssetsInBackend: $databaseOutputFoundRows->fetch_assoc()['RowCount'],
			nextCollection: $nextCollectionQuery
		);
		
		// Assemble the asset objects
		while ($row = $databaseOutput->fetch_assoc()) {

			$output->assets []= new Asset(
				active: $row['assetActive'],
				thumbnailUrl: $row['assetThumbnailUrl'],
				id: $row['assetId'],
				name: $row['assetName'],
				url: $row['assetUrl'],
				date: $row['assetDate'],
				tags: explode(',',$row['assetTags']),
				type: TYPE::from($row['typeId']),
				license: LICENSE::from($row['licenseId']),
				creator: CREATOR::from($row['creatorId'])
			);
		}

		//var_dump($output);

		LogLogic::stepOut(__FUNCTION__);
		return $output;
	}
}