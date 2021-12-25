<?php

	// chocofur

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/html.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/images.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/tools/fetch.php';

	class Creator9 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [9];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

            // Get URL list

			$config = parse_ini_file("config.ini",true);

			$tmpCollection = new AssetCollection();

			$pageCounter = 1;
			do{
				$pageContent = fetchRemoteData($config['main']['apiUrl'].$pageCounter);
				$dom = domObjectFromHtmlString($pageContent);
				foreach (getElementsByClassName($dom,'item') as $item) {
					$link = $item->getElementsByTagName("a")[0]->getAttribute('href');
					$image = $item->getElementsByTagName("img")[0]->getAttribute("data-src");
					$name = getElementsByClassName($dom,"item-description",$item)[0]->textContent;
					$name = trim($name);

					// decide whether model or shader/material
					$isMaterial = preg_match($config['main']['isMaterialRegex'],$link);
					$isBundle = preg_match($config['main']['isBundleRegex'],$link);

					if(!$isBundle && !in_array($link,$existingUrls)){
						$tmpAsset = new Asset();
						$tmpAsset->assetName = $name;
						$tmpAsset->url = $link;
						$tmpAsset->date = date("Y-m-d");
						$tmpAsset->tags = explode(" ",$tmpAsset->assetName);
						$tmpAsset->type = new Type();
						if($isMaterial){
							$tmpAsset->type->typeId = 1;
						}else{
							$tmpAsset->type->typeId = 2;
						}
						
						$tmpAsset->creator = new CreatorData();
						$tmpAsset->creator->creatorId = 9;
						$tmpAsset->license = new License();
						$tmpAsset->license->licenseId = 1;
						$tmpAsset->thumbnailUrl = $image;
						$tmpCollection->assets []= $tmpAsset;
					}

					//var_dump([$link,$image,$name,$isMaterial,$isBundle]);
				}
				$pageCounter++;
			}while(sizeof($dom->getElementsByTagName('button')) == 1);

			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			//return removeUniformBackground($imageBlob,10,10,1250);
			return $imageBlob;
		}
	}
?>