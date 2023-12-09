<?php

class AssetIoLogic{
	/**
	 * Writes all Assets in an AssetCollection to the database.
	 */
	public static function writeAssetCollectionToDatabase(AssetCollection $newAssetCollection){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Writing asset collection to DB");
		foreach ($newAssetCollection->assets as $a) {
			AssetIoLogic::writeNewAssetToDatabase($a);
		}
		LogLogic::write("Finished writing asset collection to DB");
		LogLogic::stepOut(__FUNCTION__);
	}

	/**
	 * Activates all assets in an AssetCollection.
	 */
	public static function activateAssetCollection(AssetCollection $assetCollection){
		foreach ($assetCollection->assets as $a) {
			AssetIoLogic::activateAsset($a);
		}
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
		$sqlResult = DatabaseLogic::runQuery($sql,[intval($assetId)]);
		
		$row = $sqlResult->fetch_assoc();
		return $row['assetUrl'];
	}

	public static function addAssetClickByAssetId(int $assetId){
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		DatabaseLogic::runQuery($sql,[intval($assetId)]);
	}

	public static function writeNewAssetToDatabase(Asset $newAsset){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Inserting Asset: ".$newAsset->url);

		// Base Asset
		$sql = "INSERT INTO Asset (assetId, assetName, assetUrl, assetThumbnailUrl, assetDate, assetClicks, licenseId, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?);";
		$parameters = [$newAsset->name, $newAsset->url,$newAsset->thumbnailUrl,$newAsset->date, 0 ,$newAsset->license->value,$newAsset->type->value,$newAsset->creator->value];
		$result = DatabaseLogic::runQuery($sql,$parameters);

		// Tags
		foreach ($newAsset->tags as $tag) {
			$sql = "INSERT INTO Tag (assetId,tagName) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
			$parameters = [$newAsset->url,$tag];
			DatabaseLogic::runQuery($sql,$parameters);
		}

		// Quirks
		foreach ($newAsset->quirks as $quirk) {
			$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
			$parameters = [$newAsset->url,$quirk->value];
			DatabaseLogic::runQuery($sql,$parameters);
		}

		LogLogic::stepOut(__FUNCTION__);
	}

	public static function getAssets(AssetQuery $query): AssetCollection{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Loading assets based on query: ".var_export($query, true));

		

		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT SQL_CALC_FOUND_ROWS assetId,assetUrl,assetThumbnailUrl,assetName,assetActive,assetDate,assetClicks,licenseId,typeId,creatorId,assetTags,quirkIds FROM Asset ";
		$sqlValues = [];

		// Inclusion filters

		$sqlCommand .= match ($query->includeTags) {
			true => " LEFT JOIN (SELECT assetId, GROUP_CONCAT(tagName SEPARATOR ',') AS assetTags FROM Tag GROUP BY assetId ) AllTags USING (assetId) ",
			default => " LEFT JOIN (SELECT NULL as assetId, NULL as assetTags) AllTags USING (assetId) "
		};
		
		$sqlCommand .= match($query->includeQuirks) {
			true => " LEFT JOIN (SELECT assetId, GROUP_CONCAT(quirkId SEPARATOR ',') AS quirkIds FROM Quirk GROUP BY assetId ) AllQuirks USING (assetId) ",
			default => " LEFT JOIN (SELECT NULL as assetId, NULL as quirkIds) AllQuirks USING (assetId) "
		};

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

			try{
				$quirks = [];
				foreach (array_filter(explode(",",$row['quirkIds'])) as $q) {
					$quirks []= QUIRK::from(intval($q));
				}

				$output->assets []= new Asset(
					status: ASSET_STATUS::from($row['assetActive']),
					thumbnailUrl: $row['assetThumbnailUrl'],
					id: $row['assetId'],
					name: $row['assetName'],
					url: $row['assetUrl'],
					date: $row['assetDate'],
					tags: explode(',',$row['assetTags']),
					type: TYPE::from($row['typeId']),
					license: LICENSE::from($row['licenseId']),
					creator: CREATOR::from($row['creatorId']),
					quirks: $quirks
				);
			}catch(Throwable $e){
				// do nothing
			}
			
		}

		LogLogic::stepOut(__FUNCTION__);
		return $output;
	}
}