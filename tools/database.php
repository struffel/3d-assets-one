<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/strings.php';

	function WriteAssetCollectionToDatabase(AssetCollection $newAssetCollection){
		foreach ($newAssetCollection->assets as $a) {
			writeAssetToDatabase($a);
		}
	}

	function writeAssetToDatabase(Asset $newAsset){

		// Base Asset
		$sql = "INSERT INTO Asset (AssetId, AssetSlug, AssetName, AssetUrl, AssetDate, LicenseId, TypeId, CreatorId) VALUES (NULL, GetRandomId8(), ?, ?, ?, ?, ?, ?);";
		$parameters = [$newAsset->assetName, $newAsset->url,$newAsset->date,$newAsset->license->licenseId,$newAsset->type->typeId,$newAsset->creator->creatorId];
		$result = runQuery($sql,$parameters);

		// Tags
		foreach ($newAsset->tags as $tag) {
			$sql = "INSERT INTO Tag (AssetId,TagName) VALUES ((SELECT AssetId FROM Asset WHERE AssetUrl=?),?);";
			$parameters = [$newAsset->url,$tag];
			runQuery($sql,$parameters);
		}
	}

	function loadAssetsFromDatabase(AssetQuery $query): AssetCollection{

		// Begin defining SQL string and parameters for prepared statement
		$sql = "SELECT ";
		$sqlParameters = array();

		$requiredColumns = array();
		$requiredTables = array();

		$requiredTablesForFilter = [
			"tag" => ["Tag"],
			"assetSlug" => [""],
			"creatorSlug" => ["Creator"],
			"creatorId" => ["Creator"],
			"licenseSlug" => ["License"],
			"typeSlug" => ["Type"],
		];

		$requiredTablesForInclude = [
			"asset" => [""],
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
			"asset" => ["AssetId","AssetName","AssetSlug","AssetUrl","AssetDate"],
			"tag" => ["GROUP_CONCAT(TagName SEPARATOR ',') as AssetTags"],
			"creator" => ["CreatorId","CreatorSlug","CreatorName"],
			"license" => ["LicenseId","LicenseSlug","LicenseName"],
			"type" => ["TypeId","TypeSlug","TypeName"],
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

		$sql .= " WHERE TRUE ";

		// FILTERS

		// Tags
		if($query->filter->tag){
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
		if($query->filter->assetSlug){
			$sqlParameters []= implode(",",$query->filter->assetSlug);
			$sql .= " AND FIND_IN_SET(AssetSlug,?) ";
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
		if(isset($query->limit) && isset($query->offset)){
			$sql .= " GROUP BY AssetId LIMIT ".onlyNumbers($query->limit)." OFFSET ".onlyNumbers($query->offset)."; ";
		}else if(isset($query->limit)){
			$sql .= " GROUP BY AssetId LIMIT ".onlyNumbers($query->limit)."; ";
		}else{
			$sql .= " GROUP BY AssetId; ";
		}
		

		$sqlResult = runQuery($sql,$sqlParameters);

		$output = new AssetCollection();



		while($row = $sqlResult->fetch_assoc()) {
			$newAsset = new Asset();

			if($query->include->asset){
				$newAsset->assetId = $row['AssetId'] ?? NULL;
				$newAsset->assetSlug = $row['AssetSlug'] ?? NULL;
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
			
			$output->assets []=$newAsset;
			
		}

		$output->totalNumberOfAssets = -1;
		
		return $output;
	}

	function initializeDatabaseConnection(){
		$loginData = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/../_logins/mysql.ini');

		// Create connection
		if(!isset($GLOBALS['MYSQL'])){
			$GLOBALS['MYSQL'] = new mysqli($loginData['servername'], $loginData['username'], $loginData['password']);
			createLog("Initialized DB connection to: ".$loginData['servername'],"INFO");
		}
		
		// Check connection
		if ($GLOBALS['MYSQL']->connect_error) {
			die("Connection failed: " . $GLOBALS['MYSQL']->connect_error);
		}
		$GLOBALS['MYSQL']->query("use ".$loginData['dbname']);
		createLog("Selected DB: ".$loginData['dbname'],"INFO");
	}

	function runQuery($sql,$parameters){
		createLog("Received query to run: ".$sql." ( ".implode(",",$parameters).")","INFO");
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
					createLog("Prepared Statement OK","INFO");
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
				createLog("Query OK","INFO");
			}
		}
		
		return $result;
	}
?>