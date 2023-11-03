<?php

	// polyhaven

	class CreatorFetcher2 extends CreatorFetcher{
		public CREATOR $creator = CREATOR::POLYHAVEN;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			// Contact API and get new assets
			$targetUrl = $config['apiUrl'];

			// Prepare asset collection
			$tmpCollection = new AssetCollection();
			$result = FetchLogic::fetchRemoteJson($targetUrl);

			// Iterate through result
			foreach ($result as $key => $phAsset) {

				$url = $config['viewUrlBase'].$key;

				if(!in_array($url,$existingUrls)){

					$tmpAsset = new Asset(
						id: NULL,
						url: $url,
						date: date('Y-m-d',$phAsset['date_published']),
						name: $phAsset['name'],
						tags: $phAsset['tags'],
						thumbnailUrl: $config['thumbnailUrlBase'].$key.".png?height=512",
						type: TYPE::from($config['types'][$phAsset['type']]),
						license: LICENSE::CC0,
						creator: $this->creator,
						quirks: [QUIRK::ADS]
					);

					$tmpCollection->assets[] = $tmpAsset;
					LogLogic::write("Found new asset: ".$tmpAsset->url);
				}
			}

			return $tmpCollection;
		}
	}