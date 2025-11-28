<?php

namespace asset;

use creator\Creator;
use asset\Quirk;

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
}
