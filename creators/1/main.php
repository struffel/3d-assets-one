<?php

	// ambientCG

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';

	class Creator1 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [1];
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

			// Contact API and get new assets
			$config = parse_ini_file('config.ini',true);

			$initialParameters = [
				"limit"=>100,
				"offset"=>0,
				"include"=>"displayData,tagData"
			];

			$targetUrl = $config['main']['apiUrl']."?".http_build_query($initialParameters);

			// Prepare asset collection

			$tmpCollection = new AssetCollection();

			while($targetUrl != ""){

				$result = json_decode(file_get_contents($targetUrl),true);

				// Iterate through result

				foreach ($result['foundAssets'] as $asset) {
					$tmpAsset = new Asset();

					$tmpAsset->url = $asset['shortLink'];
					$tmpAsset->date = $asset['releaseDate'];
					$tmpAsset->assetName = $asset['displayName'];
					$tmpAsset->tags = array_unique($asset['tags']);

					$tmpAsset->type = new Type();
					$tmpAsset->type->typeId = $config['types'][$asset['dataType']];

					$tmpAsset->license = new License();
					$tmpAsset->license->licenseId = 1;

					$tmpAsset->creator = new CreatorData();
					$tmpAsset->creator->creatorId = 1;

					if(!in_array($tmpAsset->url,$existingUrls)){
						$tmpCollection->assets[] = $tmpAsset;
						
					}
				}

				$targetUrl = $result['nextPageHttp'] ?? "";
			}
				
			$tmpCollection->totalNumberOfAssets = sizeof($tmpCollection->assets);
			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return "bar";
		}
	}
?>