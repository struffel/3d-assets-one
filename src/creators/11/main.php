<?php

	// rawcatalog

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	class Creator11 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [11];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

            // Get URL 

			$config = parse_ini_file("config.ini",true);

			$tmpCollection = new AssetCollection();
			$targetUrl = $config['main']['apiUrl'];

			// Parse XML
			
			$sourceData = new SimpleXMLElement(file_get_contents($targetUrl));

			$countAssets = 0;
			foreach ($config['xmlparsing'] as $xPathPrefix => $typeId) {
				//echo "--- TYPE $typeId : $xPathPrefix ---";
				//var_dump(sizeof($sourceData->xpath("$xpathPrefix//file")));
				
				foreach( $sourceData->xpath("$xPathPrefix//file") as $asset){
					$tmpAsset = new Asset();
					$tmpAsset->url = $asset->url;

					if($countAssets < $config['main']['maxAssetsPerRound'] && !in_array($tmpAsset->url,$existingUrls)){
						$tmpAsset->assetName = $asset->name;
						$tmpAsset->date = $asset->updated;

						// Tags

						$tmpAsset->tags = [];

						foreach ($asset->tags->tag as $t) {
							$tmpAsset->tags []= $t;
						}

						$tmpAsset->type = new Type();
						$tmpAsset->type->typeId = $typeId;
						$tmpAsset->creator = new CreatorData();
						$tmpAsset->creator->creatorId = 11;
						$tmpAsset->license = new License();
						$tmpAsset->license->licenseId = 0;
						$tmpAsset->thumbnailUrl = $asset->cover;

						$tmpCollection->assets []= $tmpAsset;
						$countAssets++;
					}
				}
				
			}

			
			//die();
			/*
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
			*/
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