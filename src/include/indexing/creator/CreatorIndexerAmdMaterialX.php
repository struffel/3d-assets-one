<?php

namespace indexing\creator;

use asset\Asset;
use asset\License;
use asset\Type;
use asset\AssetCollection;
use creator\Creator;

use indexing\CreatorIndexer;
use DateTime;
use fetch\WebItemReference;
use misc\Image;

// amd materialx

class CreatorIndexerAmdMaterialX extends CreatorIndexer
{

	protected Creator $creator = Creator::GPUOPENMATLIB;

	private string $apiUrl = 'https://api.matlib.gpuopen.com/api/materials/?limit=50&ordering=-published_date&status=Published&updateKey=1&offset=0';
	private string $tagApiUrl = 'https://api.matlib.gpuopen.com/api/tags/';
	private string $urlTemplate = 'https://matlib.gpuopen.com/main/materials/all?material=#ID#';
	private string $previewImageTemplate = 'https://image.matlib.gpuopen.com/#ID#.jpeg';
	private string $excludeTitleRegex = "/^TH: /";
	private int $maxAssetsPerRound = 1;

	public function findNewAssets(array $existingUrls): AssetCollection
	{

		$tmpCollection = new AssetCollection();
		$targetUrl = $this->apiUrl;
		// Limit number of assets to avoid excessive calls to the tag API
		$countAssets = 0;
		do {
			$apiJson = new WebItemReference($targetUrl)->fetch()->parseAsJson();
			foreach ($apiJson['results'] as $amdAsset) {
				if ($countAssets < $this->maxAssetsPerRound && !preg_match($this->excludeTitleRegex, $amdAsset['title'])) {

					$url = str_replace('#ID#', $amdAsset['id'], $this->urlTemplate);
					if (!in_array($url, $existingUrls)) {

						$tags = [];

						foreach ($amdAsset['tags'] as $t) {
							$tags[] = new WebItemReference($this->tagApiUrl . $t)->fetch()->parseAsJson()['title'];
						}

						$tmpAsset = new Asset(
							id: NULL,
							url: $url,
							name: $amdAsset['title'],
							date: new DateTime($amdAsset['published_date']),
							tags: $tags,
							type: Type::PBR_MATERIAL,
							license: License::APACHE_2_0,
							creator: Creator::GPUOPENMATLIB,
							thumbnailUrl: str_replace(
								'#ID#',
								$amdAsset['renders_order'][0],
								$this->previewImageTemplate
							),
							rawThumbnailData: new WebItemReference(
								url: str_replace(
									'#ID#',
									$amdAsset['renders_order'][0],
									$this->previewImageTemplate
								)
							)->fetch()->content,
						);

						$tmpCollection->assets[] = $tmpAsset;
						$countAssets++;
					}
				}
			}
			$targetUrl = $apiJson['next'] ?? null;
		} while ($targetUrl != null && $countAssets < $this->maxAssetsPerRound);

		return $tmpCollection;
	}

	public function fetchThumbnailImage(string $url): string
	{
		return Image::removeUniformBackground(new WebItemReference($url)->fetch()->content, 10, 10, 0.015);
	}
}
