<?php

	// noemotionshdr

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/html.php';

	class Creator7 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [7];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

			$config = parse_ini_file("config.ini",true);

            $tmpCollection = new AssetCollection();

			foreach ($config['urlList'] as $url) {
                $name = explode("=",$url)[1];
                $date = "2010-".preg_split('/=|_/',$url)[1];
                $category = ucfirst(substr(pathinfo($url)['filename'],3));
                $thumbnailUrl = "http://noemotionhdrs.net/Previews/772x386/$category/$name.jpg";
                $tmpAsset = new Asset();
                $tmpAsset->assetName = $name;
                $tmpAsset->url = $url;
                $tmpAsset->date = $date;
                $tmpAsset->tags = ['Sky',$category];
                $tmpAsset->type = new Type();
                $tmpAsset->type->typeId = 4;
                $tmpAsset->creator = new CreatorData();
                $tmpAsset->creator->creatorId = 7;
                $tmpAsset->license = new License();
                $tmpAsset->license->licenseId = 5;
                $tmpAsset->thumbnailUrl = $thumbnailUrl;
                if(!in_array($tmpAsset->url,$existingUrls)){
                    $tmpCollection->assets []= $tmpAsset;
                }
            }
			
            $tmpCollection->totalNumberOfAssets = sizeof($tmpCollection->assets);
			
			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			return $imageBlob;
		}
	}
?>