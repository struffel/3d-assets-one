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
	/**
	 * 
	 * @param null|int $id 
	 * @param null|string $creatorGivenId 
	 * @param string $title 
	 * @param string $url 
	 * @param AssetType $type 
	 * @param Creator $creator 
	 * @param ScrapedAssetStatus $status 
	 * @param array<string> $tags 
	 * @param null|string $rawThumbnailData 
	 * @return void 
	 */
	public function __construct(
		?int $id,
		?string $creatorGivenId,
		string $title,
		string $url,
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
			date: new DateTime(),
			type: $this->type,
			creator: $this->creator,
			tags: $this->tags,
			status: $this->status->toStoredAssetStatus(),
			lastSuccessfulValidation: NULL,
		);
	}
}
