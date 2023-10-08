<?php

	// ambientCG

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	class Creator1 extends CreatorFetcher{
		private final int $creatorId = 1;
		
		function findNewAssets():AssetCollection{
			LogLogic::stepIn(__FUNCTION__);
			LogLogic::write("Start looking for new assets");

			// Get existing URLs
			$existingUrls = $this->getExistingUrls();

			// Contact API and get new assets
			$initialParameters = [
				"limit"=>100,
				"offset"=>0,
				"include"=>"displayData,tagData,imageData"
			];

			$targetUrl = $this->config['main']['apiUrl']."?".http_build_query($initialParameters);

			// Prepare asset collection
			$tmpCollection = new AssetCollection();

			while($targetUrl != ""){

				$result = FetchLogic::fetchRemoteJson($targetUrl,$this->httpHeaders);

				// Iterate through result
				foreach ($result['foundAssets'] as $acgAsset) {
					$tmpAsset = new Asset();

					$tmpAsset->url = $acgAsset['shortLink'];
					$tmpAsset->thumbnailUrl = $acgAsset['previewImage']['512-PNG'];
					$tmpAsset->date = $acgAsset['releaseDate'];
					$tmpAsset->assetName = $acgAsset['displayName'];
					$tmpAsset->tags = array_unique($acgAsset['tags']);

					$tmpAsset->type = new Type();
					$tmpAsset->type->typeId = $this->config['types'][$acgAsset['dataType']];

					$tmpAsset->license = new License();
					$tmpAsset->license->licenseId = 1;

					$tmpAsset->creator = new CreatorData();
					$tmpAsset->creator->creatorId = 1;

					if(!in_array($tmpAsset->url,$existingUrls)){
						$tmpCollection->assets[] = $tmpAsset;
						LogLogic::write("Found new asset: ".$tmpAsset->url);
					}
				}
				
				$targetUrl = $result['nextPageHttp'] ?? "";	
			}
				
			$tmpCollection->totalNumberOfAssets = sizeof($tmpCollection->assets);
			LogLogic::stepOut(__FUNCTION__);
			return $tmpCollection;
		}
		function postProcessThumbnail(string $imageBlob): string{
			return $imageBlob;
		}
	}
?>