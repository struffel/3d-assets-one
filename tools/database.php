<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/strings.php';

	function WriteAssetCollectionToDatabase(AssetCollection $newAssetCollection){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Writing asset collection to DB");
		foreach ($newAssetCollection->assets as $a) {
			writeAssetToDatabase($a);
		}
		createLog("Finished writing asset collection to DB");
		changeLogIndentation(false,__FUNCTION__);
	}

	function activateAssetCollection(AssetCollection $assetCollection){
		foreach ($assetCollection->assets as $a) {
			activateAsset($a);
		}
	}

	function activateAsset(Asset $asset){
		runQuery("UPDATE Asset SET AssetActive = '1' WHERE AssetId = ?",[$asset->assetId]);
	}

	function loadCreatorsFromDatabase(){
		$sql = "SELECT * FROM Creator;";
		$sqlResult = runQuery($sql);
		$output = [];
		while($row = $sqlResult->fetch_assoc()){
			$output []= $row;
		}
		return $output;
	}

	function writeAssetToDatabase(Asset $newAsset){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Inserting Asset: ".$newAsset->url);


		// Base Asset
		$sql = "INSERT INTO Asset (AssetId, AssetName, AssetUrl, AssetThumbnailUrl, AssetDate, LicenseId, TypeId, CreatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?);";
		$parameters = [$newAsset->assetName, $newAsset->url,$newAsset->thumbnailUrl,$newAsset->date,$newAsset->license->licenseId,$newAsset->type->typeId,$newAsset->creator->creatorId];
		$result = runQuery($sql,$parameters);

		// Tags
		foreach ($newAsset->tags as $tag) {
			$sql = "INSERT INTO Tag (AssetId,TagName) VALUES ((SELECT AssetId FROM Asset WHERE AssetUrl=?),?);";
			$parameters = [$newAsset->url,$tag];
			runQuery($sql,$parameters);
		}
		changeLogIndentation(false,__FUNCTION__);
	}

	function loadAssetsFromDatabase(AssetQuery $query): AssetCollection{
		changeLogIndentation(true,__FUNCTION__);
		createLog("Loading assets based on query: ".var_export($query, true));

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
			$sql .= " WHERE AssetActive != 0 ";
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
			$sql .= " LIMIT ".onlyNumbers($query->limit)." OFFSET ".onlyNumbers($query->offset)."; ";
		}else if(isset($query->limit)){
			$sql .= " LIMIT ".onlyNumbers($query->limit)."; ";
		}
		

		$sqlResult = runQuery($sql,$sqlParameters);
		$sqlResultCount = runQuery("SELECT FOUND_ROWS() as Count;");

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

		
		changeLogIndentation(false,__FUNCTION__);
		return $output;
	}

	function initializeDatabaseConnection(){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Initializing DB Connection");
		
		$loginData = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/../_logins/mysql.ini');

		// Create connection
		if(!isset($GLOBALS['MYSQL'])){
			$GLOBALS['MYSQL'] = new mysqli($loginData['servername'], $loginData['username'], $loginData['password']);
			createLog("Initialized DB connection to: ".$loginData['servername']);
		}
		
		// Check connection
		if ($GLOBALS['MYSQL']->connect_error) {
			createLog("Connection failed: " . $GLOBALS['MYSQL']->connect_error,"SQL-ERROR");
		}
		$GLOBALS['MYSQL']->query("use ".$loginData['dbname']);
		createLog("Selected DB: ".$loginData['dbname']);
		changeLogIndentation(false,__FUNCTION__);
	}

	function runQuery($sql,$parameters = []){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Received SQL query to run: ".$sql." (".implode(",",$parameters).")");
		
		if(!isset($GLOBALS['MYSQL'])){
			initializeDatabaseConnection();
		}
		if(sizeof($parameters) > 0){
			$dataType = str_repeat('s',sizeof($parameters));
			$statement = $GLOBALS['MYSQL']->prepare($sql);
			if($statement){
				$statement->bind_param($dataType, ...$parameters);
				$statement->execute();
				$result = $statement->get_result();
				if($GLOBALS['MYSQL']->error){
					createLog("Prepared statement execution ERROR: ".$GLOBALS['MYSQL']->error,"SQL-ERROR");
					//die($GLOBALS['MYSQL']->error);
				}else{
					createLog("Prepared Statement OK");
				}
			}else{
				createLog("Prepared statement preparation ERROR: ".$GLOBALS['MYSQL']->error,"SQL-ERROR");
				//die($GLOBALS['MYSQL']->error);
			}
		}else{
			$result = $GLOBALS['MYSQL']->query($sql);
			if($GLOBALS['MYSQL']->error){
				createLog("Query ERROR: ".$GLOBALS['MYSQL']->error,"SQL-ERROR");
				//die($GLOBALS['MYSQL']->error);
			}else{
				createLog("Query OK");
			}
		}
		
		changeLogIndentation(false,__FUNCTION__);
		return $result;
	}
?>