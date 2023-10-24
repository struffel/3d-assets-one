<?php

	// polyhaven

	class CreatorFetcher2 extends CreatorFetcher{
		private CREATOR $creator = CREATOR::POLYHAVEN;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			// Contact API and get new assets
			$targetUrl = $config['apiUrl'];

			// Prepare asset collection
			$tmpCollection = new AssetCollection();
			$result = FetchLogic::fetchRemoteJson($targetUrl);

			// Iterate through result
			foreach ($result as $key => $phAsset) {

				if(!in_array($phAsset->url,$existingUrls)){

					$tmpAsset = new Asset(
						url: $config['main']['viewUrlBase'].$key,
						date: date('Y-m-d',$phAsset['date_published']),
						name: $phAsset['name'],
						tags: $phAsset['tags'],
						thumbnailUrl: $config['main']['thumbnailUrlBase'].$key.".png?height=512",
						type: TYPE::from($config['types'][$phAsset['type']]),
						license: LICENSE::CC0,
						creator: $this->creator
					);

					$tmpCollection->assets[] = $tmpAsset;
					LogLogic::write("Found new asset: ".$tmpAsset->url);
				}
			}

			return $tmpCollection;
		}
	}