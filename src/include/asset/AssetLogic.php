<?php

namespace asset;

use creator\Creator;
use indexing\CreatorIndexer;
use misc\Database;

class AssetLogic
{

	public static function filterTagArray(array $inputArray)
	{
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

	public static function getUrlById(string $assetId): string
	{
		$sql = "SELECT assetUrl,creatorId FROM Asset WHERE assetId = ? LIMIT 1;";
		$sqlResult = Database::runQuery($sql, [intval($assetId)]);

		$row = $sqlResult->fetch_assoc();

		$creator = Creator::from($row['creatorId']);
		$creatorFetcher = $creator->getIndexer();

		return $creatorFetcherprocessUrl($row['assetUrl']);
	}

	public static function addAssetClickById(int $assetId)
	{
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		DatabaseLogic::runQuery($sql, [intval($assetId)]);
	}

	public static function saveAssetToDatabase(Asset $asset)
	{

		Log::stepIn(__FUNCTION__);

		if ($asset->id) {
			Log::write("Updating Asset with id: " . $asset->id);

			// Base Asset
			$sql = "UPDATE Asset SET assetName=?,assetActive=?,assetUrl=?,assetThumbnailUrl=?,assetDate=?,licenseId=?,typeId=?,creatorId=?,lastSuccessfulValidation=? WHERE assetId = ?";
			$parameters = [$asset->name, $asset->status->value, $asset->url, $asset->thumbnailUrl, $asset->date, $asset->license->value, $asset->type->value, $asset->creator->value, $asset->lastSuccessfulValidation, $asset->id];
			DatabaseLogic::runQuery($sql, $parameters);

			// Tags
			DatabaseLogic::runQuery("DELETE FROM Tag WHERE assetId = ?", [$asset->id]);
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES (?,?);";
				$parameters = [$asset->id, $tag];
				DatabaseLogic::runQuery($sql, $parameters);
			}

			// Quirks

			DatabaseLogic::runQuery("DELETE FROM Quirk WHERE assetId = ?", [$asset->id]);
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES (?,?);";
				$parameters = [$asset->id, $quirk->value];
				DatabaseLogic::runQuery($sql, $parameters);
			}
		} else {
			Log::write("Inserting new asset with url:" . $asset->url);

			// Base Asset
			$sql = "INSERT INTO Asset (assetId, assetActive,assetName, assetUrl, assetThumbnailUrl, assetDate, assetClicks, licenseId, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$parameters = [$asset->status->value, $asset->name, $asset->url, $asset->thumbnailUrl, $asset->date, 0, $asset->license->value, $asset->type->value, $asset->creator->value];
			DatabaseLogic::runQuery($sql, $parameters);

			// Tags
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url, $tag];
				DatabaseLogic::runQuery($sql, $parameters);
			}

			// Quirks
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url, $quirk->value];
				DatabaseLogic::runQuery($sql, $parameters);
			}
		}

		Log::stepOut(__FUNCTION__);
		return $asset;
	}

	public static function getAssets(AssetQuery $query): AssetCollection
	{
		Log::stepIn(__FUNCTION__);
		Log::write("Loading assets based on query: " . var_export($query, true));



		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT SQL_CALC_FOUND_ROWS assetId,assetUrl,assetThumbnailUrl,assetName,assetActive,assetDate,assetClicks,lastSuccessfulValidation,licenseId,typeId,creatorId,assetTags,quirkIds FROM Asset ";
		$sqlValues = [];

		// Joins

		$sqlCommand .= " LEFT JOIN (SELECT assetId, GROUP_CONCAT(tagName SEPARATOR ',') AS assetTags FROM Tag GROUP BY assetId ) AllTags USING (assetId) ";
		$sqlCommand .= " LEFT JOIN (SELECT assetId, GROUP_CONCAT(quirkId SEPARATOR ',') AS quirkIds FROM Quirk GROUP BY assetId ) AllQuirks USING (assetId) ";

		$sqlCommand .= " WHERE TRUE ";


		foreach ($query->filterTag as $tag) {
			$sqlCommand .= " AND assetId IN (SELECT assetId FROM Tag WHERE tagName = ? ) ";
			$sqlValues[] = $tag;
		}

		foreach ($query->filterAvoidQuirk as $quirk) {
			$sqlCommand .= " AND assetId NOT IN (SELECT assetId FROM Quirk WHERE quirkId = ? ) ";
			$sqlValues[] = $quirk->value;
		}


		if (sizeof($query->filterAssetId) > 0) {
			$ph = DatabaseLogic::generatePlaceholder($query->filterAssetId);
			$sqlCommand .= " AND assetId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $query->filterAssetId);
		}

		if (sizeof($query->filterType) > 0) {
			$ph = DatabaseLogic::generatePlaceholder($query->filterType);
			$sqlCommand .= " AND typeId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $query->filterType);
		}

		if (sizeof($query->filterLicense) > 0) {
			$ph = DatabaseLogic::generatePlaceholder($query->filterLicense);
			$sqlCommand .= " AND licenseId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $query->filterLicense);
		}

		if (sizeof($query->filterCreator) > 0) {
			$ph = DatabaseLogic::generatePlaceholder($query->filterCreator);
			$sqlCommand .= " AND creatorId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $query->filterCreator);
		}

		if ($query->filterStatus !== NULL) {
			$sqlCommand .= " AND assetActive=? ";
			$sqlValues[] = $query->filterStatus;
		}

		// Sort
		$sqlCommand .= match ($query->sort) {

			// Options for public display
			SORTING::LATEST => " ORDER BY assetDate DESC, assetId DESC ",
			SORTING::OLDEST => " ORDER BY assetDate ASC, assetId ASC ",
			SORTING::RANDOM => " ORDER BY RAND() ",
			SORTING::POPULAR => " ORDER BY ( (assetClicks + 10) / POW( ABS( DATEDIFF( NOW(),assetDate ) ) + 1 , 1.3 ) ) DESC, assetDate DESC, assetId DESC ",

			// Options for internal editor (potentially less optimized)
			SORTING::LEAST_CLICKED => " ORDER BY assetClicks ASC ",
			SORTING::MOST_CLICKED => " ORDER BY assetClicks DESC ",
			SORTING::LEAST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.assetId = Asset.assetId) ASC ",
			SORTING::MOST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.assetId = Asset.assetId) DESC ",
			SORTING::LATEST_VALIDATION_SUCCESS => " ORDER BY lastSuccessfulValidation DESC, RAND() ",
			SORTING::OLDEST_VALIDATION_SUCCESS => " ORDER BY lastSuccessfulValidation ASC, RAND() "
		};

		// Offset and Limit
		if ($query->limit != NULL) {
			// Clean up query
			$query->limit = max(1, $query->limit);
			$query->offset = max(0, $query->offset);

			$sqlCommand .= " LIMIT ? OFFSET ? ";
			$sqlValues[] = $query->limit;
			$sqlValues[] = $query->offset;
		}


		// Fetch data from DB
		$databaseOutput = DatabaseLogic::runQuery($sqlCommand, $sqlValues);
		$databaseOutputFoundRows = DatabaseLogic::runQuery("SELECT FOUND_ROWS() as RowCount;");

		// Prepare the final asset collection
		$output = new AssetCollection(
			totalNumberOfAssetsInBackend: $databaseOutputFoundRows->fetch_assoc()['RowCount']
		);

		// Add a query for more assets, if there are any 
		if ($output->totalNumberOfAssetsInBackend > $query->offset + $query->limit) {
			$nextCollectionQuery = clone $query;
			$nextCollectionQuery->offset += $nextCollectionQuery->limit;
			$output->nextCollection = $nextCollectionQuery;
		}


		// Assemble the asset objects
		while ($row = $databaseOutput->fetch_assoc()) {

			$quirks = [];
			foreach (array_filter(explode(",", $row['quirkIds'] ?? "")) as $q) {
				$quirks[] = QUIRK::from(intval($q));
			}

			$tags = array_filter(explode(',', $row['assetTags'] ?? ""));

			$output->assets[] = new Asset(
				status: AssetStatus::from($row['assetActive']),
				thumbnailUrl: $row['assetThumbnailUrl'],
				id: $row['assetId'],
				name: $row['assetName'],
				url: $row['assetUrl'],
				date: $row['assetDate'],
				tags: $tags,
				type: TYPE::from($row['typeId']),
				license: LICENSE::from($row['licenseId']),
				creator: CREATOR::from($row['creatorId']),
				quirks: $quirks,
				lastSuccessfulValidation: new DateTime($row['lastSuccessfulValidation'])
			);
		}

		Log::stepOut(__FUNCTION__);
		return $output;
	}
}
