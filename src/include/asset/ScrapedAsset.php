<?php

namespace asset;

use creator\Creator;
use DateTime;

/**
 * The main asset class.
 * It represents one PBR material, 3D model or other asset.
 */
class ScrapedAsset extends Asset
{
	public function __construct(
		?int $id,
		?string $creatorGivenId,
		string $title,
		string $url,
		DateTime $date,
		AssetType $type,
		Creator $creator,
		public ScrapedAssetStatus $status,
		array $tags = [],
		public ?string $rawThumbnailData = NULL,
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

	public function toStoredAsset(): StoredAsset
	{
		return new StoredAsset(
			id: $this->id,
			creatorGivenId: $this->creatorGivenId,
			title: $this->title,
			url: $this->url,
			date: $this->date,
			type: $this->type,
			creator: $this->creator,
			tags: $this->tags,
			status: $this->status->toStoredAssetStatus(),
			lastSuccessfulValidation: NULL,
		);
	}
}
