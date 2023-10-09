<?php

	// polyhaven

	class CreatorFetcher2 extends CreatorFetcher{
		private final int $creatorId = 2;
		function findNewAssets():AssetCollection{

			// Get existing URLs
			$existingUrls = $this->getExistingUrls();

			// Contact API and get new assets
			$targetUrl = $this->config['apiUrl'];

			// Prepare asset collection
			$tmpCollection = new AssetCollection();
			$result = FetchLogic::fetchRemoteJson($targetUrl);

			// Iterate through result
			foreach ($result as $key => $asset) {
				if(!in_array($tmpAsset->url,$existingUrls)){

					$tmpAsset = new Asset(
						url: $this->config['main']['viewUrlBase'].$key,
						date: date('Y-m-d',$asset['date_published']),
						assetName: $asset['name'],
						tags: array_unique($asset['tags']),
						thumbnailUrl: $this->config['main']['thumbnailUrlBase'].$key.".png?height=512",
						type: new Type(
							typeId: $this->config['types'][$asset['type']]
						),
						license: new License(
							licenseId: 1
						),
						creator: new Creator(
							creatorId: 2
						)
	
					);

					$tmpCollection->assets[] = $tmpAsset;
					LogLogic::write("Found new asset: ".$tmpAsset->url);
				}
			}
				
			$tmpCollection->totalNumberOfAssets = sizeof($tmpCollection->assets);
			return $tmpCollection;
		}

		function postProcessThumbnail(string $imageBlob): string{
			return $imageBlob;
		}
	}
?>