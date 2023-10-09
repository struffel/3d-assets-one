<?php

	// cgbookcase

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	class Creator5 extends CreatorFetcher{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$existingUrls = $this->getExistingUrls();

			$urlList = FetchLogic::fetchRemoteData($this->config["main"]["urlList"]);
			$urlList = str_replace("\n","",$urlList);
			$urlArray = explode(",",$urlList);
			$urlArray = array_filter($urlArray);
			$urlArray = array_map('trim', $urlArray);
			

			$tmpCollection = new AssetCollection();

			$maxAssets = 5;
			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){
					$siteContent = FetchLogic::fetchRemoteData($url);

					$tmpAsset = new Asset();

					$metaTags = HtmlLogic::readMetatagsFromHtmlString($siteContent);

					$tmpAsset->assetName = $metaTags['tex1:name'];
					$tmpAsset->url = $url;
					$tmpAsset->date = $metaTags['tex1:release-date'];
					$tmpAsset->tags = explode(",",$metaTags['tex1:tags']);
					$tmpAsset->type = new Type();
					$tmpAsset->type->typeId = $this->config['types'][$metaTags['tex1:type']];
					$tmpAsset->license = new License();
					$tmpAsset->license->licenseId = $this->config['licenses'][$metaTags['tex1:license']];
					$tmpAsset->creator = new CreatorData();
					$tmpAsset->creator->creatorId = 5;

					$tmpAsset->thumbnailUrl = $metaTags['tex1:preview-image'];

					$tmpCollection->assets []= $tmpAsset;

					$countAssets++;
					if($countAssets >= $maxAssets){
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