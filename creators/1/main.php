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
			$existingUrls = array();
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

			// Contact API and get new assets

			$parameters = [
				"limit"=>100,
				"offset"=>0,
				"include"=>"displayData"
			];

			$config = parse_ini_file('config.ini',true);
			$targetUrl = $config['main']['apiUrl']."?".http_build_query($parameters);
			$result = json_decode(file_get_contents($targetUrl),true);

			// Prepare asset collection
			$tmpCollection = new AssetCollection();

			// Iterate through result

			foreach ($result['foundAssets'] as $asset) {
				$tmpAsset = new Asset();

				$tmpAsset->url = $asset['shortLink'];
				$tmpAsset->date = $asset['releaseDate'];
				$tmpAsset->assetName = $asset['displayName'];

				$tmpAsset->type = new Type();
				$tmpAsset->type->typeId = $config['types'][$asset['dataType']];

				$tmpAsset->license = new License();
				$tmpAsset->license->licenseId = 1;

				$tmpAsset->creator = new CreatorData();
				$tmpAsset->creator->creatorId = 1;

				$tmpCollection->assets[] = $tmpAsset;
			}
			
			
			
			$tmpCollection->numberOfResults = 1;
			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return "bar";
		}
	}
?>