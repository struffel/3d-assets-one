<?php

	// hdri workshop

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	class Creator12 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [12];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

			// Load configuration
			$config = parse_ini_file("config.ini",true);

			$apiOutput = getJsonFromUrl($config['main']['apiUrl']);

			$tmpCollection = new AssetCollection();
			foreach($apiOutput as $asset){
				if(!in_array($asset['fullUrl'],$existingUrls)){
					$tmpAsset = new Asset();
                    $tmpAsset->assetName = $asset['name'];
                    $tmpAsset->url = $asset['fullUrl'];
                    $tmpAsset->date = date("Y-m-d");
                    $tmpAsset->tags = explode(" ",$tmpAsset->assetName);
                    $tmpAsset->type = new Type();
                    $tmpAsset->type->typeId = 4;
                    $tmpAsset->creator = new CreatorData();
                    $tmpAsset->creator->creatorId = 12;
                    $tmpAsset->license = new License();
                    $tmpAsset->license->licenseId = 0;
                    $tmpAsset->thumbnailUrl = $asset['fullUrlThumb'];
                    $tmpCollection->assets []= $tmpAsset;
				}
			}

			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			return $imageBlob;
		}
		function generateThumbnailFetchingHeaders(): array{
			return [];
		}
	}
?>