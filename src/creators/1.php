<?php

	// ambientCG

	class CreatorFetcher1 extends CreatorFetcher{
		private final int $creatorId = 1;
		
		public function findNewAssets():AssetCollection{
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

			$this->config;

			$targetUrl = $this->config['apiUrl']."?".http_build_query($initialParameters);

			// Prepare asset collection
			$tmpCollection = new AssetCollection();

			while($targetUrl != ""){
				$result = FetchLogic::fetchRemoteJson($targetUrl);

				// Iterate through result
				foreach ($result['foundAssets'] as $acgAsset) {

					if(!in_array($tmpAsset->url,$existingUrls)){

						$tmpAsset = new Asset(
							url:$acgAsset['shortLink'],
							thumbnailUrl: $acgAsset['previewImage']['512-PNG'],
							date: $acgAsset['releaseDate'],
							assetName: $acgAsset['displayName'],
							tags: array_unique($acgAsset['tags']),
							type: new Type(
								typeId:$this->config['types'][$acgAsset['dataType']]
							),
							license: new License(
								licenseId: 1
							),
							creator: new Creator(
								creatorId: 1
							)
						);

						$tmpCollection->assets[] = $tmpAsset;
						LogLogic::write("Found new asset: ".$tmpAsset->url);
					}

				}
				
				$targetUrl = $result['nextPageHttp'] ?? "";	
			}
			LogLogic::stepOut(__FUNCTION__);
			return $tmpCollection;
		}
		function postProcessThumbnail(string $imageBlob): string{
			return $imageBlob;
		}
	}
?>