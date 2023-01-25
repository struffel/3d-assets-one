<?php

	// texturecan

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/html.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/images.php';

	class Creator6 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [6];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

			$config = parse_ini_file("config.ini",true);

			$urlList = fetchRemoteData($config["main"]["urlList"]);
			$urlList = str_replace("\n","",$urlList);
			$urlArray = explode(",",$urlList);
			$urlArray = array_filter($urlArray);
			$urlArray = array_map('trim', $urlArray);
			

			$tmpCollection = new AssetCollection();

			$maxAssets = 5;
			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){
					$siteContent = fetchRemoteData($url);

					$tmpAsset = new Asset();

					$metaTags = readMetatagsFromHtmlString($siteContent);

					$tmpAsset->assetName = $metaTags['tex1:name'];
					$tmpAsset->url = $url;
					$tmpAsset->date = $metaTags['tex1:release-date'];
					$tmpAsset->tags = explode(",",$metaTags['tex1:tags']);
					$tmpAsset->type = new Type();
					$tmpAsset->type->typeId = $config['types'][$metaTags['tex1:type']];
					$tmpAsset->license = new License();
					$tmpAsset->license->licenseId = $config['licenses'][$metaTags['tex1:license']];
					$tmpAsset->creator = new CreatorData();
					$tmpAsset->creator->creatorId = 6;

					$tmpAsset->thumbnailUrl = $metaTags['tex1:preview-image'];

					$tmpCollection->assets []= $tmpAsset;

					$countAssets++;
					if($countAssets >= $maxAssets){
						break;
					}
				}
			}
			
			

			return $tmpCollection;
		}
		function refreshAssetById(int $assetId):Asset{
			return new Asset();
		}
		function postProcessThumbnail(string $imageBlob): string{
			return removeUniformBackground($imageBlob,2,2,0.015);
		}
	}
?>