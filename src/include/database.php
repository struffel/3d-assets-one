<?php

class DatabaseLogic{
	public static function writeAssetCollection(AssetCollection $newAssetCollection){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Writing asset collection to DB");
		foreach ($newAssetCollection->assets as $a) {
			writeAssetToDatabase($a);
		}
		LogLogic::write("Finished writing asset collection to DB");
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function activateAssetCollection(AssetCollection $assetCollection){
		foreach ($assetCollection->assets as $a) {
			DatabaseLogic::activateAsset($a);
		}
	}

	public static function activateAsset(Asset $asset){
		DatabaseLogic::runQuery("UPDATE Asset SET AssetActive = '1' WHERE AssetId = ?",[$asset->assetId]);
	}

	public static function getCreators(){
		$sql = "SELECT CreatorId,CreatorSlug,CreatorName,CreatorDescription,BaseUrl,( SELECT COUNT(AssetId) FROM Asset WHERE Asset.AssetActive = 1 AND Asset.CreatorId = Creator.CreatorId ) as AssetCount FROM Creator;";
		$sqlResult = DatabaseLogic::runQuery($sql);
		$output = [];
		while($row = $sqlResult->fetch_assoc()){
			$output []= $row;
		}
		return $output;
	}

	public static function getUrlFromAssetId(string $assetId) : string{
		$sql = "SELECT AssetUrl FROM Asset WHERE AssetId = ? LIMIT 1;";
		$sqlParameters = [intval($assetId)];
		$sqlResult = DatabaseLogic::runQuery($sql,$sqlParameters);
		
		$row = $sqlResult->fetch_assoc();
		return $row['AssetUrl'];
	}

	public static function addAssetClickByAssetId($assetId){
		$sql = "INSERT INTO Click(AssetId,Day,Count) VALUES (?,NOW(),1) ON DUPLICATE KEY UPDATE Count = Count+1;";
		$sqlParameters = [intval($assetId)];
		DatabaseLogic::runQuery($sql,$sqlParameters);
	}

	public static function writeAssetToDatabase(Asset $newAsset){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Inserting Asset: ".$newAsset->url);


		// Base Asset
		$sql = "INSERT INTO Asset (AssetId, AssetName, AssetUrl, AssetThumbnailUrl, AssetDate, LicenseId, TypeId, CreatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?);";
		$parameters = [$newAsset->assetName, $newAsset->url,$newAsset->thumbnailUrl,$newAsset->date,$newAsset->license->licenseId,$newAsset->type->typeId,$newAsset->creator->creatorId];
		$result = runQuery($sql,$parameters);

		// Tags
		foreach ($newAsset->tags as $tag) {
			$sql = "INSERT INTO Tag (AssetId,TagName) VALUES ((SELECT AssetId FROM Asset WHERE AssetUrl=?),?);";
			$parameters = [$newAsset->url,$tag];
			DatabaseLogic::runQuery($sql,$parameters);
		}
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function getAssets(AssetQuery $query): AssetCollection{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Loading assets based on query: ".var_export($query, true));

		// Begin defining SQL string and parameters for prepared statement
		$sql = "SELECT SQL_CALC_FOUND_ROWS ";
		$sqlParameters = array();

		$requiredColumns = array();
		$requiredTables = array();

		$requiredTablesForFilter = [
			"active" => [""],
			"tag" => ["Tag"],
			"assetId" => [""],
			"creatorSlug" => ["Creator"],
			"creatorId" => ["Creator"],
			"licenseSlug" => ["License"],
			"typeSlug" => ["Type"],
		];

		$requiredTablesForInclude = [
			"asset" => [""],
			"internal" => [""],
			"tag" => ["Tag"],
			"creator" => ["Creator"],
			"license" => ["License"],
			"type" => ["Type"],
		];

		$requiredTablesJoinOn = [
			"Tag" => "AssetId",
			"Creator"=>"CreatorId",
			"License"=>"LicenseId",
			"Type"=>"TypeId"
		];

		$requiredColumnsForInclude = [
			"asset" => ["AssetId","AssetName","AssetUrl","AssetDate"],
			"tag" => ["GROUP_CONCAT(TagName SEPARATOR ',') as AssetTags"],
			"creator" => ["CreatorId","CreatorSlug","CreatorName"],
			"license" => ["LicenseId","LicenseSlug","LicenseName"],
			"type" => ["TypeId","TypeSlug","TypeName"],
			"internal" => ["AssetActive","AssetThumbnailUrl"]
		];

		foreach ($query->filter as $key => $value) {
			if($value){
				$requiredTables = array_merge($requiredTables,$requiredTablesForFilter[$key]);
			}	
		}

		foreach ($query->include as $key => $value) {
			if($value){
				$requiredTables = array_merge($requiredTables,$requiredTablesForInclude[$key]);
			}	
		}

		foreach ($query->include as $key => $value) {
			if($value){
				$requiredColumns = array_merge($requiredColumns,$requiredColumnsForInclude[$key]);
			}
		}


		$requiredColumns = array_unique(array_filter($requiredColumns));
		$requiredTables = array_unique(array_filter($requiredTables));

		

		$sql .= implode(",",$requiredColumns);

		$sql .= " FROM Asset ";

		foreach ($requiredTables as $table) {
			$sql .= " LEFT JOIN $table USING (".$requiredTablesJoinOn[$table].") ";
		}

		if($query->filter->active === NULL){
			$sql .= " WHERE TRUE ";
		}else if ($query->filter->active) {
			$sql .= " WHERE AssetActive = 1 ";
		}else{
			$sql .= " WHERE AssetActive = 0 ";
		}

		// FILTERS

		// Tags
		if($query->filter->tag){
			$query->filter->tag = array_map('trim',array_filter($query->filter->tag));
			foreach ($query->filter->tag as $i) {
				$sqlParameters []= $i; 
				$sql .= " AND AssetId IN (SELECT AssetId FROM Tag WHERE TagName = ?) ";
			}
		}

		// Creators
		if($query->filter->creatorSlug){
			$sqlParameters []= implode(",",$query->filter->creatorSlug);
			$sql .= " AND FIND_IN_SET(CreatorSlug,?) ";
		}

		// Creators
		if($query->filter->creatorId){
			$sqlParameters []= implode(",",$query->filter->creatorId);
			$sql .= " AND FIND_IN_SET(CreatorId,?) ";
		}

		// Asset slug
		if($query->filter->assetId){
			$sqlParameters []= implode(",",$query->filter->assetId);
			$sql .= " AND FIND_IN_SET(AssetId,?) ";
		}

		// Licenses
		if($query->filter->licenseSlug){
			$sqlParameters []= implode(",",$query->filter->licenseSlug);
			$sql .= " AND FIND_IN_SET(LicenseSlug,?) ";
		}

		// Types
		if($query->filter->typeSlug){
			$sqlParameters []= implode(",",$query->filter->typeSlug);
			$sql .= " AND FIND_IN_SET(TypeSlug,?) ";
		}

		$sql .= " GROUP BY AssetId ";

		switch ($query->sort ?? "") {
			case 'latest':
				$sql .= " ORDER BY AssetDate DESC , AssetId ASC ";
				break;
			case 'oldest':
				$sql .= " ORDER BY AssetDate ASC , AssetId ASC ";
				break;
			case 'random':
				$sql .= " ORDER BY RAND() ";
				break;
			case 'id_desc':
				$sql .= " ORDER BY AssetID DESC ";
				break;
			case 'id_asc':
				$sql .= " ORDER BY AssetID ASC ";
				break;
			default:
				$sql .= " ORDER BY AssetDate DESC , AssetId ASC ";
				break;
		}

		if(isset($query->limit) && isset($query->offset)){
			$sql .= " LIMIT ".StringLogic::onlyNumbers($query->limit)." OFFSET ".StringLogic::onlyNumbers($query->offset)."; ";
		}else if(isset($query->limit)){
			$sql .= " LIMIT ".StringLogic::onlyNumbers($query->limit)."; ";
		}
		

		$sqlResult = DatabaseLogic::runQuery($sql,$sqlParameters);
		$sqlResultCount = DatabaseLogic::runQuery("SELECT FOUND_ROWS() as Count;");

		$output = new AssetCollection();

		$output->totalNumberOfAssets = $sqlResultCount->fetch_assoc()['Count'];

		while($row = $sqlResult->fetch_assoc()) {

			$newAsset = new Asset();

			if($query->include->asset){
				$newAsset->assetId = $row['AssetId'] ?? NULL;
				$newAsset->assetName = $row['AssetName'] ?? NULL;
				$newAsset->url = $row['AssetUrl'] ?? NULL;
				$newAsset->date = $row['AssetDate'] ?? NULL;
			}

			if($query->include->tag){
				$newAsset->tags = array_filter(explode(",",$row['AssetTags']));
			}
			
			if($query->include->type){
				$newAsset->type = new Type();
				$newAsset->type->typeId = $row['TypeId'] ?? NULL;
				$newAsset->type->typeSlug = $row['TypeSlug'] ?? NULL;
				$newAsset->type->typeName = $row['TypeName'] ?? NULL;
			}

			if($query->include->license){
				$newAsset->license = new License();
				$newAsset->license->licenseId = $row['LicenseId'] ?? NULL;
				$newAsset->license->licenseSlug = $row['LicenseSlug'] ?? NULL;
				$newAsset->license->licenseName = $row['LicenseName'] ?? NULL;
			}

			if($query->include->creator){
				$newAsset->creator = new CreatorData();
				$newAsset->creator->creatorId = $row['CreatorId'] ?? NULL;
				$newAsset->creator->creatorSlug = $row['CreatorSlug'] ?? NULL;
				$newAsset->creator->creatorName = $row['CreatorName'] ?? NULL;
			}
			
			if($query->include->internal){
				$newAsset->active = $row['AssetActive'] ?? NULL;
				$newAsset->thumbnailUrl = $row['AssetThumbnailUrl'] ?? NULL;

			}
			
			$output->assets []=$newAsset;
			
		}

		
		LogLogic::stepOut(__FUNCTION__);
		return $output;
	}

	private static mysqli $connection;
	public static function initializeConnection(){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Initializing DB Connection");
		

		// Create connection
		if(!isset(DatabaseLogic::$connection)){
			DatabaseLogic::$connection = new mysqli(getenv("3D1_DB_SERVER"), getenv("3D1_DB_USERNAME"), getenv("3D1_DB_PASSWORD"));
			LogLogic::write("Initialized DB connection to: ".getenv("3D1_DB_SERVER"));
		}
		
		// Check connection
		if (DatabaseLogic::$connection->connect_error) {
			LogLogic::write("Connection failed: " . DatabaseLogic::$connection->connect_error,"SQL-ERROR");
		}

		$query = "use ".getenv("3D1_DB_NAME").";";
		DatabaseLogic::$connection->query($query);
		LogLogic::write("Selected DB: ".getenv("3D1_DB_NAME"));
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function runQuery($sql,$parameters = []){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Received SQL query to run: ".$sql." (".implode(",",$parameters).")");
		
		if(!isset(DatabaseLogic::$connection)){
			DatabaseLogic::initializeConnection();
		}
		if(sizeof($parameters) > 0){
			$dataType = str_repeat('s',sizeof($parameters));
			$statement = DatabaseLogic::$connection->prepare($sql);
			if($statement){
				$statement->bind_param($dataType, ...$parameters);
				$statement->execute();
				$result = $statement->get_result();
				if(DatabaseLogic::$connection->error){
					LogLogic::write("Prepared statement execution ERROR: ".DatabaseLogic::$connection->error,"SQL-ERROR");
					//die(DatabaseLogic::$connection->error);
				}else{
					LogLogic::write("Prepared Statement OK");
				}
			}else{
				LogLogic::write("Prepared statement preparation ERROR: ".DatabaseLogic::$connection->error,"SQL-ERROR");
				//die(DatabaseLogic::$connection->error);
			}
		}else{
			$result = DatabaseLogic::$connection->query($sql);
			if(DatabaseLogic::$connection->error){
				LogLogic::write("Query ERROR: ".DatabaseLogic::$connection->error,"SQL-ERROR");
				//die(DatabaseLogic::$connection->error);
			}else{
				LogLogic::write("Query OK");
			}
		}
		
		LogLogic::stepOut(__FUNCTION__);
		return $result;
	}
}
?>