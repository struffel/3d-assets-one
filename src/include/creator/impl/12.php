<?php

	// hdri workshop

	class CreatorFetcher12 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::HDRIWORKSHOP;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$apiOutput = FetchLogic::fetchRemoteJson($config['apiUrl']);

			$tmpCollection = new AssetCollection();
			foreach($apiOutput as $hdriWorkshopAsset){
				if(!in_array($hdriWorkshopAsset['fullUrl'],$existingUrls)){
					$tmpAsset = new Asset(
						id: NULL,
						name: $hdriWorkshopAsset['name'],
						url: $hdriWorkshopAsset['fullUrl'],
						tags: explode(" ",$hdriWorkshopAsset['name']),
						type: TYPE::HDRI,
						creator: $this->creator,
						license: LICENSE::CUSTOM,
						thumbnailUrl: $hdriWorkshopAsset['fullUrlThumb'],
						quirks:[],
						date:date("Y-m-d")
					);

                    $tmpCollection->assets []= $tmpAsset;
				}
			}

			return $tmpCollection;
		}
	}