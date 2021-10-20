<?php

	

	function writeAssetToDatabase(Asset $newAsset){
		
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
			"asset" => ["AssetId","AssetName","AssetSlug","AssetUrl"],
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
				$sql .= " AND AssetId IN (SELECT AssetId WHERE TagName = ?) ";
			}
		}

		// Creators
		if($query->filter->creatorSlug){
			$sqlParameters []= implode(",",$query->filter->creatorSlug);
			$sql .= " AND FIND_IN_SET(CreatorSlug,?) ";
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

		$sql .= " GROUP BY AssetId; ";

		

		$sqlResult = runQuery($sql,$sqlParameters);

		$output = new AssetCollection();



		while($row = $sqlResult->fetch_assoc()) {
			$newAsset = new Asset();

			if($query->include->asset){
				$newAsset->assetId = $row['AssetId'] ?? NULL;
				$newAsset->assetSlug = $row['AssetSlug'] ?? NULL;
				$newAsset->assetName = $row['AssetName'] ?? NULL;
				$newAsset->assetUrl = $row['AssetUrl'] ?? NULL;
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
		}
		
		// Check connection
		if ($GLOBALS['MYSQL']->connect_error) {
			die("Connection failed: " . $GLOBALS['MYSQL']->connect_error);
		}
		$GLOBALS['MYSQL']->query("use ".$loginData['dbname']);
	}

	function runQuery($sql,$parameters){
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
				if(!$result){
					die($GLOBALS['MYSQL']->error);
				}
			}else{
				die($GLOBALS['MYSQL']->error);
			}
		}else{
			$result = $GLOBALS['MYSQL']->query($sql);
			if(!$result){
				die($GLOBALS['MYSQL']->error);
			}
		}
		
		
		
		return $result;
	}
?>