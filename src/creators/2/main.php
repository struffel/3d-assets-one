<?php

	// polyhaven

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	class Creator2 extends CreatorFetcher{
		private final int $creatorId = 2;
		function findNewAssets():AssetCollection{

			// Get existing URLs
			$existingUrls = $this->getExistingUrls();

			// Contact API and get new assets
			$targetUrl = $this->config['main']['apiUrl'];

			// Prepare asset collection
			$tmpCollection = new AssetCollection();
			$result = FetchLogic::fetchRemoteJson($targetUrl,$this->httpHeaders);

			// Iterate through result
			foreach ($result as $key => $asset) {
				$tmpAsset = new Asset();

				$tmpAsset->url = $this->config['main']['viewUrlBase'].$key;
				$tmpAsset->date = date('Y-m-d',$asset['date_published']);
				$tmpAsset->assetName = $asset['name'];
				$tmpAsset->tags = array_unique($asset['tags']);
				$tmpAsset->thumbnailUrl = $this->config['main']['thumbnailUrlBase'].$key.".png?height=512";

				$tmpAsset->type = new Type();
				$tmpAsset->type->typeId = $this->config['types'][$asset['type']];

				$tmpAsset->license = new License();
				$tmpAsset->license->licenseId = 1;

				$tmpAsset->creator = new CreatorData();
				$tmpAsset->creator->creatorId = 2;

				if(!in_array($tmpAsset->url,$existingUrls)){
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