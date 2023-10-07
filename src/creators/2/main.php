<?php

	// polyhaven

	require_once $_SERVER['DOCUMENT_ROOT'].'/../functions/init.php';

	class Creator2 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [2];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

            //LogLogic::write("Found existing URLs: ".implode($existingUrls));

			// Contact API and get new assets
            $config = parse_ini_file('config.ini',true);
			$targetUrl = $config['main']['apiUrl'];

			// Prepare asset collection
			$tmpCollection = new AssetCollection();

            $result = getJsonFromUrl($targetUrl);

            // Iterate through result

			$count = 0;

            foreach ($result as $key => $asset) {
                $tmpAsset = new Asset();

                $tmpAsset->url = $config['main']['viewUrlBase'].$key;
                $tmpAsset->date = date('Y-m-d',$asset['date_published']);
                $tmpAsset->assetName = $asset['name'];
                $tmpAsset->tags = array_unique($asset['tags']);
				$tmpAsset->thumbnailUrl = $config['main']['thumbnailUrlBase'].$key.".png?height=512";

                $tmpAsset->type = new Type();
                $tmpAsset->type->typeId = $config['types'][$asset['type']];

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