<?php

	// 3dtextures

	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/json.php';

	class Creator13 extends CreatorInterface{
		function findNewAssets():AssetCollection{

			// Get existing Assets

			$query = new AssetQuery();
			$query->filter->creatorId = [13];
			$query->filter->active = NULL;
			$result = loadAssetsFromDatabase($query);
			$existingUrls = [""];
			foreach ($result->assets as $asset) {
				$existingUrls []= $asset->url;
			}

			$config = parse_ini_file("config.ini",true);
            $tmpCollection = new AssetCollection();

            $page = 1;
            $wpOutput=[];
            $fetchedList = [];
            $continue = true;

            $processedAssets = 0;
            $maxAssets = $config['main']['maxAssetsPerRound'];

            while($continue){
                $wpLink=$config['main']['apiUrl']."product?_embed&per_page=100&page=$page&orderby=date";
                $wpOutput=getJsonFromUrl($wpLink);
                if($wpOutput){
                    $page++;

                    foreach($wpOutput as $wpPost){
                        if(!in_array($wpPost['link'],$existingUrls)){
                            $tmpAsset = new Asset();

                            // Basic information
                            $tmpAsset->assetName = $wpPost['title']['rendered'];
                            $tmpAsset->url = $wpPost['link'];
                            $tmpAsset->date = $wpPost['date'];

                            // Tags
                            foreach ($wpPost['_embedded']['wp:term'] as $wpTermCategory) {
                                //var_dump($wpTermCategory);
                                // embeddedTerm = Distinction between "Tags" or "Category"
                                // Both will be treated as tags
                                foreach ($wpTermCategory as $taglikeObject) {
                                    $tmpAsset->tags []= $taglikeObject['name'];
                                }
                                
                            }

                            $oldErrorReportingLevel = error_reporting();
                            error_reporting(E_ERROR | E_PARSE);

                            // 1st attempt
                            try{
                                $tmpAsset->thumbnailUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['large']['s3']['url'];
                            }catch(Throwable $e){
                                createLog($e->getMessage()." / 1st attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
                            }

                            // 2nd attempt
                            if(!isset($tmpAsset->thumbnailUrl)){
                                try{
                                    $tmpAsset->thumbnailUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
                                }catch(Throwable $e){
                                    createLog($e->getMessage()." / 2nd attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
                                }
                            }


                            error_reporting($oldErrorReportingLevel);

                            // Test if any attempt worked
                            if(!isset($tmpAsset->thumbnailUrl)){
                                continue;
                            }

                            $tmpAsset->type = new Type();
                            $tmpAsset->type->typeId = 1;
                            
                            $tmpAsset->license = new License();
                            $tmpAsset->license->licenseId = 1;

                            $tmpAsset->creator = new CreatorData();
                            $tmpAsset->creator->creatorId = 13;

                            $tmpCollection->assets []= $tmpAsset;

                            $processedAssets++;  
                        }
                        if($processedAssets >= $maxAssets){
                            $continue = false;
                            break;
                        }
                    }

                }else{
                    $continue = false;
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