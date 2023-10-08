<?php

	// 3dtextures

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	class Creator4 extends CreatorFetcher{
		function findNewAssets():AssetCollection{

			// Get existing Assets
			$existingUrls = $this->getExistingUrls();
			
			$tmpCollection = new AssetCollection();

			$page = 1;
			$wpOutput=[];
			$fetchedList = [];
			$continue = true;

			$processedAssets = 0;
			$maxAssets = $this->config['main']['maxAssetsPerRound'];

			while($continue){
				$wpLink=$this->config['main']['apiUrl']."posts?_embed&per_page=100&page=$page&orderby=date";
				$wpOutput=FetchLogic::fetchRemoteJson($wpLink,$this->httpHeaders);
				if($wpOutput){
					$page++;

					foreach($wpOutput as $wpPost){
						if(!in_array($wpPost['link'],$existingUrls)){
							$tmpAsset = new Asset();
							$tmpAsset->assetName = $wpPost['title']['rendered'];
							$tmpAsset->url = $wpPost['link'];
							$tmpAsset->date = $wpPost['date'];

							// Tags
							foreach ($wpPost['_embedded']['wp:term'] as $embeddedCategory) {
								foreach($embeddedCategory as $embeddedObject){
									$tmpAsset->tags []= $embeddedObject['name'];
								}
							}

							$oldErrorReportingLevel = error_reporting();
							error_reporting(E_ERROR | E_PARSE);

							// 1st attempt
							try{
								$tmpAsset->thumbnailUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['square']['source_url'];
							}catch(Throwable $e){
								LogLogic::write($e->getMessage()." / 1st attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
							}

							// 2nd attempt
							if(!isset($tmpAsset->thumbnailUrl)){
								try{
									$tmpAsset->thumbnailUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
								}catch(Throwable $e){
									LogLogic::write($e->getMessage()." / 2nd attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
								}
							}

							// 3rd attempt
							if(!isset($tmpAsset->thumbnailUrl)){
								try{
									$tmpAsset->thumbnailUrl = $wpPost['jetpack_featured_media_url'];
								}catch(Throwable $e){
									LogLogic::write($e->getMessage()." / 3rd attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
								}
							}

							error_reporting($oldErrorReportingLevel);

							// Test if any attempt worked
							if(!isset($tmpAsset->thumbnailUrl)){
								LogLogic::write("All attempts failed. Thumbnail could not be resolved. Skipping... / ".$tmpAsset->url,"IMG-ERROR");
								continue;
							}

							$tmpAsset->type = new Type();
							$tmpAsset->type->typeId = 1;
							
							$tmpAsset->license = new License();
							$tmpAsset->license->licenseId = 1;

							$tmpAsset->creator = new CreatorData();
							$tmpAsset->creator->creatorId = 4;

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
		function postProcessThumbnail(string $imageBlob): string{
			return ImageLogic::removeUniformBackground($imageBlob,3,3,0.015);
		}
	}
?>