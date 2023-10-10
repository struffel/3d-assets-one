<?php

	// cgbookcase

	class CreatorFetcher5 extends CreatorFetcher{

		private int $creatorId = 5;

		function findNewAssets():AssetCollection{

			// Get existing Assets

			$existingUrls = $this->getExistingUrls();
			
			$urlArray = FetchLogic::fetchRemoteCommaSeparatedList($this->config['urlList']);

			$tmpCollection = new AssetCollection();

			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){

					$siteContent = FetchLogic::fetchRemoteData($url);
					$metaTags = HtmlLogic::readMetatagsFromHtmlString($siteContent);
					
					$tmpAsset = new Asset(
						assetName: $metaTags['tex1:name'],
						url: $url,
						date: $metaTags['tex1:release-date'],
						tags: explode(",",$metaTags['tex1:tags']),
						type: new Type(
							typeId: $this->config['types'][$metaTags['tex1:type']]
						),
						license: new License(
							licenseId: $this->config['licenses'][$metaTags['tex1:license']]
						),
						creator: new Creator(
							creatorId: $this->creatorId
						),
						thumbnailUrl: $metaTags['tex1:preview-image']
					);

					$tmpCollection->assets []= $tmpAsset;

					$countAssets++;
					if($countAssets >= $this->config->['maxAssets']){
						break;
					}
				}
			}
			
			

			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			return removeUniformBackground($imageBlob,2,2,0.015);
		}
		function generateThumbnailFetchingHeaders(): array{
			return [];
		}
	}
?>