<?php

	// ambientCG

	class CreatorFetcher1 extends CreatorFetcher{
		public CREATOR $creator = CREATOR::AMBIENTCG;
		
		public function findNewAssets(array $existingUrls, array $config):AssetCollection{
			LogLogic::stepIn(__FUNCTION__);
			LogLogic::write("Start looking for new assets");

			// Contact API and get new assets
			$initialParameters = [
				"limit"=>100,
				"offset"=>0,
				"include"=>"displayData,tagData,imageData"
			];

			$existingUrls = array_map(fn($u) => strtolower($u), $existingUrls);

			$targetUrl = $config['apiUrl']."?".http_build_query($initialParameters);

			// Prepare asset collection
			$tmpCollection = new AssetCollection();

			while($targetUrl != ""){
				$result = FetchLogic::fetchRemoteJson($targetUrl);

				// Iterate through result
				foreach ($result['foundAssets'] as $acgAsset) {

					if(!in_array(strtolower($acgAsset['shortLink']),$existingUrls)){

						$tmpAsset = new Asset(
							url:$acgAsset['shortLink'],
							thumbnailUrl: $acgAsset['previewImage']['512-PNG'],
							date: $acgAsset['releaseDate'],
							name: $acgAsset['displayName'],
							tags: $acgAsset['tags'],
							type: TYPE::from($config['types'][$acgAsset['dataType']]),
							license: LICENSE::CC0,
							creator: $this->creator,
							id: NULL,
							quirks:[QUIRK::ADS]
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
	}