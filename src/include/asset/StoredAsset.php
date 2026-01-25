<?php

namespace asset;

use creator\Creator;
use DateTime;
use Exception;
use log\Log;
use database\Database;

/**
 * The main asset class.
 * It represents one PBR material, 3D model or other asset.
 */
class StoredAsset extends Asset
{
	public function __construct(
		?int $id,
		?string $creatorGivenId,
		string $title,
		string $url,
		DateTime $date,
		AssetType $type,
		Creator $creator,
		array $tags = [],

		public StoredAssetStatus $status = StoredAssetStatus::ACTIVE,
		public ?DateTime $lastSuccessfulValidation = NULL,
	) {
		parent::__construct(
			id: $id,
			creatorGivenId: $creatorGivenId,
			title: $title,
			url: $url,
			date: $date,
			type: $type,
			creator: $creator,
			tags: $tags,
		);
	}

	public function getThumbnailUrl(int $size, string $extension, ?string $backgroundColor): string
	{
		$variation = strtoupper(implode("-", array_filter([$size, $extension, $backgroundColor])));
		$extension = strtolower($extension);
		$id = $this->id;
		return "/thumbnail/$variation/$id.$extension";
	}

	public function writeToDatabase()
	{

		if ($this->id) {
			Log::write("Updating asset", $this);

			// Save base asset
			$sql = "UPDATE Asset SET creatorGivenId=?, title=?,state=?,url=?,date=?,typeId=?,creatorId=?,lastSuccessfulValidation=? WHERE id = ?";
			$parameters = [$this->creatorGivenId, $this->title, $this->status->value, $this->url, $this->date, $this->type->value, $this->creator->value, $this->lastSuccessfulValidation, $this->id];
			Database::runQuery($sql, $parameters);

			// Tags
			Database::runQuery("DELETE FROM Tag WHERE id = ?", [$this->id]);
			foreach ($this->tags as $tag) {
				$sql = "INSERT INTO Tag (id,tag) VALUES (?,?);";
				$parameters = [$this->id, $tag];
				Database::runQuery($sql, $parameters);
			}
		} else {
			Log::write("Inserting new asset", $this);

			// Base Asset
			$sql = "INSERT INTO Asset (id,creatorGivenId, state, title, url, date, clicks, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?);";
			$parameters = [$this->creatorGivenId, $this->status->value, $this->title, $this->url, $this->date, 0, $this->type->value, $this->creator->value];
			Database::runQuery($sql, $parameters);

			// Add the missing id to the asset object
			$this->id = Database::runQuery("SELECT id FROM Asset WHERE url = ?;", [$this->url])->fetchArray()['id'];
			if (!$this->id) {
				throw new Exception("Failed to retrieve ID of newly inserted asset with URL " . $this->url);
			}

			// Tags
			foreach ($this->tags as $tag) {
				$sql = "INSERT INTO Tag (id,tag) VALUES (?,?);";
				$parameters = [$this->id, $tag];
				Database::runQuery($sql, $parameters);
			}
		}
	}
}
