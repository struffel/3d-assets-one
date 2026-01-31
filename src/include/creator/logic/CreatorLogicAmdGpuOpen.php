<?php

namespace creator\logic;


use asset\AssetType;
use asset\ScrapedAsset;
use asset\ScrapedAssetCollection;
use asset\ScrapedAssetStatus;
use asset\StoredAssetCollection;
use creator\Creator;
use creator\CreatorLogic;
use DateTime;
use Exception;
use fetch\WebItemReference;
use thumbnail\Thumbnail;

// amd materialx

class CreatorLogicAmdGpuOpen extends CreatorLogic
{

	protected Creator $creator = Creator::GPUOPENMATLIB;

	private string $apiUrl = 'https://api.matlib.gpuopen.com/api/materials/?limit=50&ordering=-published_date&status=Published&updateKey=1&offset=0';
	private string $tagApiUrl = 'https://api.matlib.gpuopen.com/api/tags/';
	private string $urlTemplate = 'https://matlib.gpuopen.com/main/materials/all?material=#ID#';
	private string $previewImageTemplate = 'https://image.matlib.gpuopen.com/#ID#.jpeg';
	private string $excludeTitleRegex = "/^TH: /";
	protected int $maxAssetsPerRun = 5;

	public function scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection
	{

		$tmpCollection = new ScrapedAssetCollection();
		$targetUrl = $this->apiUrl;
		// Limit number of assets to avoid excessive calls to the tag API
		do {
			$apiJson = new WebItemReference($targetUrl)->fetch()->parseAsJson();
			if ($apiJson === null || !isset($apiJson['results'])) {
				throw new Exception("Failed to fetch or parse API data.");
			}
			foreach ($apiJson['results'] as $amdAsset) {
				if (sizeof($tmpCollection) < $this->maxAssetsPerRun && !preg_match($this->excludeTitleRegex, $amdAsset['title'])) {

					$url = str_replace('#ID#', $amdAsset['id'], $this->urlTemplate);
					if (!$existingAssets->containsUrl($url)) {

						$tags = [];

						foreach ($amdAsset['tags'] as $t) {
							$tagJson = new WebItemReference($this->tagApiUrl . $t)->fetch()->parseAsJson();
							$tags[] = $tagJson['title'] ?? '';
						}

						$tmpAsset = new ScrapedAsset(
							id: NULL,
							creatorGivenId: $amdAsset['id'],
							url: $url,
							title: $amdAsset['title'],
							tags: $tags,
							type: AssetType::PBR_MATERIAL,
							creator: Creator::GPUOPENMATLIB,
							rawThumbnailData: new WebItemReference(
								url: str_replace(
									'#ID#',
									$amdAsset['renders_order'][0],
									$this->previewImageTemplate
								)
							)->fetch()->content,
							status: ScrapedAssetStatus::NEWLY_FOUND,
						);

						$tmpCollection[] = $tmpAsset;
					}
				}
			}
			$targetUrl = $apiJson['next'] ?? null;
		} while ($targetUrl != null && sizeof($tmpCollection) < $this->maxAssetsPerRun);

		return $tmpCollection;
	}
}
