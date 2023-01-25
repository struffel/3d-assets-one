<?php

	// amd materialx

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/html.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/images.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/tools/fetch.php';

	class Creator10 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [10];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

            // Get URL list

			$config = parse_ini_file("config.ini",true);

			$tmpCollection = new AssetCollection();
			$targetUrl = $config['main']['apiUrl'];
			// Limit number of assets to avoid excessive calls to the tag API
			$countAssets = 0;
			do{
				$apiJson = getJsonFromUrl($targetUrl);
				foreach ($apiJson['results'] as $asset) {
					if($countAssets < $config['main']['maxAssetsPerRound'] && !preg_match($config['main']['excludeTitleRegex'],$asset['title'])){
						$tmpAsset = new Asset();
						$tmpAsset->url = str_replace('#ID#',$asset['id'],$config['main']['urlTemplate']);
						if(!in_array($tmpAsset->url,$existingUrls)){
							$tmpAsset->assetName = $asset['title'];
							$tmpAsset->date = $asset['published_date'];

							// Tags

							$tmpAsset->tags = [];

							foreach ($asset['tags'] as $t) {
								$tmpAsset->tags []= getJsonFromUrl($config['main']['tagApiUrl'].$t)['title'];
							}

							$tmpAsset->type = new Type();
							$tmpAsset->type->typeId = 1;
							$tmpAsset->creator = new CreatorData();
							$tmpAsset->creator->creatorId = 10;
							$tmpAsset->license = new License();
							$tmpAsset->license->licenseId = 8;
							$tmpAsset->thumbnailUrl = str_replace('#ID#',$asset['renders_order'][0],$config['main']['previewImageTemplate']);

							$tmpCollection->assets []= $tmpAsset;
							$countAssets++;
						}
						
					}
				}
				$targetUrl = $apiJson['next']??null;
			}while($targetUrl != null && $countAssets < $config['main']['maxAssetsPerRound']);

			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			return removeUniformBackground($imageBlob,10,10,0.015);
			//return $imageBlob;
		}
	}
?>