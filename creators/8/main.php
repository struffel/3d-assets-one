<?php

	// benianus 3d

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/html.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/images.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/tools/fetch.php';

	class Creator8 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [8];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

            // Get URL list

			$config = parse_ini_file("config.ini",true);

			$tmpCollection = new AssetCollection();

            $maxAssets = 5;
			$countProcessed = 0;
            $countIteration = 0;
			foreach ($config['urlList'] as $url) {
                if(!in_array($url,$existingUrls)){

                    $pageContent = fetchRemoteData($url);
                    $pageMetaTags = readMetatagsFromHtmlString($pageContent);

                    $tmpAsset = new Asset();
                    $tmpAsset->assetName = $pageMetaTags['og:title'];
                    $tmpAsset->url = $url;
                    $tmpAsset->date = date("Y-m-d");
                    $tmpAsset->tags = explode(" ",$tmpAsset->assetName);
                    $tmpAsset->type = new Type();
                    $tmpAsset->type->typeId = 2;
                    $tmpAsset->creator = new CreatorData();
                    $tmpAsset->creator->creatorId = 8;
                    $tmpAsset->license = new License();
                    $tmpAsset->license->licenseId = 1;
                    $tmpAsset->thumbnailUrl = $config['imageList'][$countIteration];
                    $tmpCollection->assets []= $tmpAsset;

                    $countProcessed++;
                }

                if($countProcessed >= $maxAssets){
                    break;
                }
                $countIteration++;
            }
			
			

			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			return removeUniformBackground($imageBlob,10,10,1250);
		}
	}
?>