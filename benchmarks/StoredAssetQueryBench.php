<?php

use asset\StoredAssetQuery;
use creator\Creator;
use misc\StringUtil;
use PhpBench\Attributes as Bench;

class StoredAssetQueryBench
{
	private array $queryStrings;

	public function __construct()
	{
		// Setup test data for benchmarks
		$this->queryStrings = [
			"wood",
			"sand wet",
			"chair",
			"wood floor dark"
		];
	}

	#[Bench\Revs(15)]
	#[Bench\Iterations(3)]
	#[Bench\Warmup(2)]
	public function benchGetAllAssetsCreatorCount(): void
	{
		$query = new StoredAssetQuery();
		$result = $query->executeCountByCreator();
		assert(count($result) == count(Creator::cases()));
	}

	#[Bench\Revs(15)]
	#[Bench\Iterations(3)]
	#[Bench\Warmup(2)]
	public function benchGetAllAssetsWithoutTags(): void
	{
		$query = new StoredAssetQuery();
		$result = $query->execute(includeTags: false);
		assert(count($result) >= 0);
	}

	#[Bench\Revs(15)]
	#[Bench\Iterations(3)]
	#[Bench\Warmup(2)]
	public function benchGetLimitedAssetsWithoutTags(): void
	{
		$query = new StoredAssetQuery(limit: 150);
		$result = $query->execute(includeTags: false);
		assert(count($result) >= 0);
	}

	#[Bench\Revs(15)]
	#[Bench\Iterations(3)]
	#[Bench\Warmup(2)]
	public function benchGetAllAssetsWithTags(): void
	{
		$query = new StoredAssetQuery();
		$result = $query->execute(includeTags: true);
		assert(count($result) >= 0);
	}

	#[Bench\Revs(15)]
	#[Bench\Iterations(3)]
	#[Bench\Warmup(2)]
	public function benchGetLimitedAssetsWithTags(): void
	{
		$query = new StoredAssetQuery(limit: 150);
		$result = $query->execute(includeTags: true);
		assert(count($result) >= 0);
	}
}
