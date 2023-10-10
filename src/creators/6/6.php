<?php

	// texturecan

	class CreatorFetcher6 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			$this->creatorId = 6;

			// Get existing Assets
			$existingUrls = $this->getExistingUrls();
			$urlArray = FetchLogic::fetchRemoteCommaSeparatedList($this->config['urlList']);

			$tmpCollection = new AssetCollection();

			$maxAssets = 5;
			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){
					$siteContent = fetchRemoteData($url);

					$tmpAsset = new Asset();

					$metaTags = readMetatagsFromHtmlString($siteContent);

					$tmpAsset->assetName = $metaTags['tex1:name'];
					$tmpAsset->url = $url;
					$tmpAsset->date = $metaTags['tex1:release-date'];
					$tmpAsset->tags = explode(",",$metaTags['tex1:tags']);
					$tmpAsset->type = new Type();
					$tmpAsset->type->typeId = $config['types'][$metaTags['tex1:type']];
					$tmpAsset->license = new License();
					$tmpAsset->license->licenseId = $config['licenses'][$metaTags['tex1:license']];
					$tmpAsset->creator = new CreatorData();
					$tmpAsset->creator->creatorId = 6;

					$tmpAsset->thumbnailUrl = $metaTags['tex1:preview-image'];

					$tmpCollection->assets []= $tmpAsset;

					$countAssets++;
					if($countAssets >= $this->config['maxAssets']){
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