<?php

namespace asset;

use creator\Creator;

use DateTime;
use database\Database;
use log\Log;

class StoredAssetQuery
{
	public function __construct(
		// Basics
		public ?int $offset = NULL,						// ?offset
		public ?int $limit = NULL,						// ?limit
		public ?AssetSorting $sort = AssetSorting::LATEST,		// ?sort

		// Filters
		public ?array $filterAssetId = [],		// ?id, Allows filtering for specific asset ids.
		public ?array $filterTag = [],			// ?tags, Assets must have ALL tags in the array in order to be included.
		public ?array $filterCreator = [],		// ?creator, limits the search to certain creators.
		public ?array $filterType = [],			// ?type, defines which types of asset should be included. Empty array causes all types to be included.
		public ?StoredAssetStatus $filterStatus = StoredAssetStatus::ACTIVE,				// NULL => Any status

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
		$output['type'] = array_map($enumToSlugConverter, $this->filterType);

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
	public static function fromHttpGet(?StoredAssetStatus $filterStatus = StoredAssetStatus::ACTIVE): StoredAssetQuery
	{

		// status filter (only if it's not defined in the method head)
		if ($filterStatus === NULL && isset($_GET['status']) && $_GET['status'] != "") {
			$filterStatus = StoredAssetStatus::tryFrom(intval($_GET['status']));
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
			$filterType[] = AssetType::fromSlug($typeSlug);
		}
		$filterType = array_filter($filterType);


		return new StoredAssetQuery(
			offset: intval($_GET['offset'] ?? 0),
			limit: min(intval($_GET['limit'] ?? 150), 500),
			sort: AssetSorting::fromAnyString($_GET['sort'] ?? "latest"),
			filterAssetId: $filterAssetId,
			filterTag: array_map('trim', array_filter(preg_split('/\s|,/', $_GET['q'] ?? ""))),
			filterCreator: $filterCreator,
			filterType: $filterType,
			filterStatus: $filterStatus
		);
	}

	public function execute(): StoredAssetCollection
	{

		Log::write("Loading assets based on this query", $this);

		// Begin defining SQL string and parameters for prepared statement
		$sqlCommand = " SELECT id,url,title,state,date,clicks,lastSuccessfulValidation,typeId,creatorId,tags FROM Asset ";
		$sqlValues = [];

		// Joins

		$sqlCommand .= " LEFT JOIN (SELECT id, GROUP_CONCAT(tag , ',') AS tags FROM Tag GROUP BY id ) AllTags USING (id) ";

		$sqlCommand .= " WHERE TRUE ";


		foreach ($this->filterTag as $tag) {
			$sqlCommand .= " AND id IN (SELECT id FROM Tag WHERE tag = ? ) ";
			$sqlValues[] = $tag;
		}


		if (sizeof($this->filterAssetId) > 0) {
			$ph = Database::generatePlaceholder($this->filterAssetId);
			$sqlCommand .= " AND id IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterAssetId);
		}

		if (sizeof($this->filterType ?? []) > 0) {
			$ph = Database::generatePlaceholder($this->filterType);
			$sqlCommand .= " AND typeId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterType);
		}

		if (sizeof($this->filterCreator ?? []) > 0) {
			$ph = Database::generatePlaceholder($this->filterCreator);
			$sqlCommand .= " AND creatorId IN ($ph) ";
			$sqlValues = array_merge($sqlValues, $this->filterCreator);
		}

		if ($this->filterStatus !== NULL) {
			$sqlCommand .= " AND state=? ";
			$sqlValues[] = $this->filterStatus;
		}

		// Sort
		$sqlCommand .= match ($this->sort) {

			// Options for public display
			AssetSorting::LATEST => " ORDER BY date DESC, id DESC ",
			AssetSorting::OLDEST => " ORDER BY date ASC, id ASC ",
			AssetSorting::RANDOM => " ORDER BY RANDOM() ",
			AssetSorting::POPULAR => " ORDER BY ( clicks / ABS( JULIANDAY('now') - JULIANDAY(date) ) + 1  ) DESC, date DESC, id DESC ",

			// Options for internal editor (potentially less optimized)
			AssetSorting::LEAST_CLICKED => " ORDER BY clicks ASC ",
			AssetSorting::MOST_CLICKED => " ORDER BY clicks DESC ",
			AssetSorting::LEAST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.id = Asset.id) ASC ",
			AssetSorting::MOST_TAGGED => " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.id = Asset.id) DESC ",
			AssetSorting::LATEST_VALIDATION_SUCCESS => " ORDER BY lastSuccessfulValidation DESC, RANDOM() ",
			AssetSorting::OLDEST_VALIDATION_SUCCESS => " ORDER BY lastSuccessfulValidation ASC, RANDOM() "
		};

		// Offset and Limit
		if ($this->limit != NULL) {
			// Clean up query
			$this->limit = max(1, $this->limit); // +1 to check if there are more assets available
			$this->offset = max(0, $this->offset);

			$sqlCommand .= " LIMIT ? OFFSET ? ";
			$sqlValues[] = $this->limit;
			$sqlValues[] = $this->offset;
		}

		// Fetch data from DB
		$databaseResult = Database::runQuery($sqlCommand, $sqlValues);
		$databaseOutput = [];
		while ($row = $databaseResult->fetchArray(SQLITE3_ASSOC)) {
			$databaseOutput[] = $row;
		}

		// Prepare the final asset collection
		$output = new StoredAssetCollection();

		if ($this->limit != NULL && count($databaseOutput) == $this->limit) {
			$nextCollectionQuery = clone $this;
			$nextCollectionQuery->offset += $nextCollectionQuery->limit;
			$output->nextCollection = $nextCollectionQuery;
		}

		// Assemble the asset objects
		foreach ($databaseOutput as $row) {

			$tags = array_filter(explode(',', $row['tags'] ?? ""));

			$output[] = new StoredAsset(
				status: StoredAssetStatus::from($row['state']),
				id: $row['id'],
				creatorGivenId: NULL,
				title: $row['title'],
				url: $row['url'],
				date: new DateTime($row['date']),
				tags: $tags,
				type: AssetType::from($row['typeId']),
				creator: Creator::from($row['creatorId']),
				lastSuccessfulValidation: new DateTime($row['lastSuccessfulValidation'] ?? '1970-01-01 00:00:00'),
			);
		}

		Log::write("Loaded assets", ["count" => count($output), "nextCollection" => $output->nextCollection]);

		return $output;
	}
}
