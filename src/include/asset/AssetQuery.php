<?php

namespace asset;

use creator\Creator;
use asset\Quirk;
use DateTime;
use misc\Database;
use log\Log;

class AssetQuery
{
	public function __construct(
		// Basics
		public ?int $offset = NULL,						// ?offset
		public ?int $limit = NULL,								// ?limit
		public ?Sorting $sort = Sorting::LATEST,		// ?sort

		// Filters
		public ?array $filterAssetId = [],		// ?id, Allows filtering for specific asset ids.
		public ?array $filterTag = [],			// ?tags, Assets must have ALL tags in the array in order to be included.
		public ?array $filterCreator = [],		// ?creator, limits the search to certain creators.
		public ?array $filterLicense = [],		// ?license, defines which licenes should be allowed. Empty array causes all licenses to be allowed.
		public ?array $filterType = [],			// ?type, defines which types of asset should be included. Empty array causes all types to be included.
		public ?array $filterAvoidQuirk = [],		// ?avoid, defines which quirks a site MUST NOT have to still be included. Empty array causes all quirks to be allowed.
		public ?AssetStatus $filterStatus = AssetStatus::ACTIVE,				// NULL => Any status

	) {}

	public function toHttpGet(bool $includeStatus = false): string
	{

		$enumToSlugConverter = function ($e) {
			return $e->slug();
		};

		$output = [];

		$output['q'] = implode(",", $this->filterTag);
		$output['offset'] = $this->offset;
		$output['limit'] = $this->limit;
		$output['sort'] = $this->sort->value;
		$output['id'] = $this->filterAssetId;
		$output['creator'] = array_map($enumToSlugConverter, $this->filterCreator);
		$output['license'] = array_map($enumToSlugConverter, $this->filterLicense);
		$output['type'] = array_map($enumToSlugConverter, $this->filterType);
		$output['avoid'] = array_map($enumToSlugConverter, $this->filterAvoidQuirk);

		if ($includeStatus) {
			$output['status'] = $this->filterStatus->value ?? NULL;
		}

		return http_build_query($output);
	}

	/**
	 * Generates a new AssetQuery based on the current HTTP GET parameters in $_GET.
	 * The asset status can be forced to a specific value using the method paramter 'filterStatus'.
	 * Setting filterStatus to NULL allows the status to be controlled using a HTTP parameter.
	 */
	public static function fromHttpGet(?AssetStatus $filterStatus = AssetStatus::ACTIVE): AssetQuery
	{

		// status filter (only if it's not defined in the method head)
		if ($filterStatus === NULL && isset($_GET['status']) && $_GET['status'] != "") {
			$filterStatus = AssetStatus::tryFrom(intval($_GET['status']));
		}

		// assetId filter
		$filterAssetId = [];
		foreach ($_GET['id'] ?? [] as $assetId) {
			$filterAssetId[] = intval($assetId);
		}
		$filterAssetId = array_filter($filterAssetId);

		// creator filter
		$filterCreator = [];
		foreach ($_GET['creator'] ?? [] as $creatorSlug) {
			$filterCreator[] = Creator::fromSlug($creatorSlug);
		}
		$filterCreator = array_filter($filterCreator);

		// type filter
		$filterType = [];
		foreach ($_GET['type'] ?? [] as $typeSlug) {
			$filterType[] = Type::fromSlug($typeSlug);
		}
		$filterType = array_filter($filterType);

		// license filter
		$filterLicense = [];
		foreach ($_GET['license'] ?? [] as $licenseSlug) {
			$filterLicense[] = License::fromSlug($licenseSlug);
		}
		$filterLicense = array_filter($filterLicense);

		// quirk filter
		$filterAvoidQuirk = [];
		foreach ($_GET['avoid'] ?? [] as $quirkSlug) {
			$filterAvoidQuirk[] = Quirk::fromSlug($quirkSlug);
		}
		$filterAvoidQuirk = array_filter($filterAvoidQuirk);

		return new AssetQuery(
			offset: intval($_GET['offset'] ?? 0),
			limit: min(intval($_GET['limit'] ?? 150), 500),
			sort: Sorting::fromAnyString($_GET['sort'] ?? "latest"),
			filterAssetId: $filterAssetId,
			filterTag: array_map('trim', array_filter(preg_split('/\s|,/', $_GET['q'] ?? ""))),
			filterCreator: $filterCreator,
			filterLicense: $filterLicense,
			filterType: $filterType,
			filterAvoidQuirk: $filterAvoidQuirk,
			filterStatus: $filterStatus
		);
	}

	public function execute(): AssetCollection
	{

		Log::write("Loading assets based on query: " . var_export($this, true));



		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT SQL_CALC_FOUND_ROWS assetId,assetUrl,assetThumbnailUrl,assetName,assetActive,assetDate,assetClicks,lastSuccessfulValidation,licenseId,typeId,creatorId,assetTags,quirkIds FROM Asset ";
		$sqlValues = [];

		// Joins

		$sqlCommand .= " LEFT JOIN (SELECT assetId, GROUP_CONCAT(tagName SEPARATOR ',') AS assetTags FROM Tag GROUP BY assetId ) AllTags USING (assetId) ";
		$sqlCommand .= " LEFT JOIN (SELECT assetId, GROUP_CONCAT(quirkId SEPARATOR ',') AS quirkIds FROM Quirk GROUP BY assetId ) AllQuirks USING (assetId) ";

		$sqlCommand .= " WHERE TRUE ";


		foreach ($this->filterTag as $tag) {
			$sqlCommand .= " AND assetId IN (SELECT assetId FROM Tag WHERE tagName = ? ) ";
			$sqlValues[] = $tag;
		}

		foreach ($this->filterAvoidQuirk as $quirk) {
			$sqlCommand .= " AND assetId NOT IN (SELECT assetId FROM Quirk WHERE quirkId = ? ) ";
			$sqlValues[] = $quirk->value;
		}


		if (sizeof($this->filterAssetId) > 0) {
			$ph = Database::generatePlaceholder($this->filterAssetId);
			$sqlCommand .= " AND assetId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterAssetId);
		}

		if (sizeof($this->filterType ?? []) > 0) {
			$ph = Database::generatePlaceholder($this->filterType);
			$sqlCommand .= " AND typeId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterType);
		}

		if (sizeof($this->filterLicense ?? []) > 0) {
			$ph = Database::generatePlaceholder($this->filterLicense);
			$sqlCommand .= " AND licenseId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterLicense);
		}

		if (sizeof($this->filterCreator ?? []) > 0) {
			$ph = Database::generatePlaceholder($this->filterCreator);
			$sqlCommand .= " AND creatorId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterCreator);
		}

		if ($this->filterStatus !== NULL) {
			$sqlCommand .= " AND assetActive=? ";
			$sqlValues[] = $this->filterStatus;
		}

		// Sort
		$sqlCommand .= match ($this->sort) {

			// Options for public display
			Sorting::LATEST => " ORDER BY assetDate DESC, assetId DESC ",
			Sorting::OLDEST => " ORDER BY assetDate ASC, assetId ASC ",
			Sorting::RANDOM => " ORDER BY RAND() ",
			Sorting::POPULAR => " ORDER BY ( (assetClicks + 10) / POW( ABS( DATEDIFF( NOW(),assetDate ) ) + 1 , 1.3 ) ) DESC, assetDate DESC, assetId DESC ",

			// Options for internal editor (potentially less optimized)
			Sorting::LEAST_CLICKED => " ORDER BY assetClicks ASC ",
			Sorting::MOST_CLICKED => " ORDER BY assetClicks DESC ",
			Sorting::LEAST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.assetId = Asset.assetId) ASC ",
			Sorting::MOST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.assetId = Asset.assetId) DESC ",
			Sorting::LATEST_VALIDATION_SUCCESS => " ORDER BY lastSuccessfulValidation DESC, RAND() ",
			Sorting::OLDEST_VALIDATION_SUCCESS => " ORDER BY lastSuccessfulValidation ASC, RAND() "
		};

		// Offset and Limit
		if ($this->limit != NULL) {
			// Clean up query
			$this->limit = max(1, $this->limit);
			$this->offset = max(0, $this->offset);

			$sqlCommand .= " LIMIT ? OFFSET ? ";
			$sqlValues[] = $this->limit;
			$sqlValues[] = $this->offset;
		}


		// Fetch data from DB
		$databaseOutput = Database::runQuery($sqlCommand, $sqlValues);
		$databaseOutputFoundRows = Database::runQuery("SELECT FOUND_ROWS() as RowCount;");

		// Prepare the final asset collection
		$output = new AssetCollection(
			totalNumberOfAssetsInBackend: $databaseOutputFoundRows->fetch_assoc()['RowCount']
		);

		// Add a query for more assets, if there are any 
		if ($output->totalNumberOfAssetsInBackend > $this->offset + $this->limit) {
			$nextCollectionQuery = clone $this;
			$nextCollectionQuery->offset += $nextCollectionQuery->limit;
			$output->nextCollection = $nextCollectionQuery;
		}


		// Assemble the asset objects
		while ($row = $databaseOutput->fetch_assoc()) {

			$quirks = [];
			foreach (array_filter(explode(",", $row['quirkIds'] ?? "")) as $q) {
				$quirks[] = Quirk::from(intval($q));
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
				type: Type::from($row['typeId']),
				license: License::from($row['licenseId']),
				creator: Creator::from($row['creatorId']),
				quirks: $quirks,
				lastSuccessfulValidation: new DateTime($row['lastSuccessfulValidation'])
			);
		}


		return $output;
	}
}
