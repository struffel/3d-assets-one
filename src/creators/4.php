<?php

	// 3dtextures

	class CreatorFetcher4 extends CreatorFetcher{

		$this->creatorId = 4;

		function findNewAssets():AssetCollection{

			// Get existing Assets
			$existingUrls = $this->getExistingUrls();
			
			$tmpCollection = new AssetCollection();

			$page = 1;
			$wpOutput=[];

			$processedAssets = 0;
			$maxAssets = $this->config['maxAssetsPerRound'];

			$continue = true;
			do{
				$wpLink=$this->config['apiUrl']."posts?_embed&per_page=100&page=$page&orderby=date";
				$wpOutput=FetchLogic::fetchRemoteJson($wpLink);

				if($wpOutput){
					
					foreach($wpOutput as $wpPost){

						if(!in_array($wpPost['link'],$existingUrls)){

							// Tags
							$tmpTags = [];
							foreach ($wpPost['_embedded']['wp:term'] as $embeddedCategory) {
								foreach($embeddedCategory as $embeddedObject){
									$tmpTags []= $embeddedObject['name'];
								}
							}

							// Thumbnail

							$oldErrorReportingLevel = error_reporting();
							error_reporting(E_ERROR | E_PARSE);

							// 1st attempt
							try{
								$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['square']['source_url'];
							}catch(Throwable $e){
								LogLogic::write($e->getMessage()." / 1st attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
							}

							// 2nd attempt
							if(!isset($tmpThumbnail)){
								try{
									$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
								}catch(Throwable $e){
									LogLogic::write($e->getMessage()." / 2nd attempt failed... / ".$tmpAsset->url,"IMG-ERROR");
								}
							}

							// 3rd attempt
							if(!isset($tmpThumbnail)){
								try{
									$tmpThumbnail = $wpPost['jetpack_featured_media_url'];
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
							
							// Assemble asset
							$tmpAsset = new Asset(
								assetName: $wpPost['title']['rendered'],
								url: $wpPost['link'],
								date: $wpPost['date'],
								tags: $tmpTags,
								thumbnail: $tmpThumbnail,
								type: new Type(
									typeId: 1
								),
								license: new License(
									licenseId: 1
								),
								creator: new Creator(
									creatorId: $this->creatorId;
								)

							);

							$tmpCollection->assets []= $tmpAsset;

							$processedAssets++;  
						}
						if($processedAssets >= $maxAssets){
							$continue = false;
							break;
						}
					}
					$page++;
				}else{
					$continue = false;
				}
			}while($continue);
			
			$tmpCollection->totalNumberOfAssets = sizeof($tmpCollection->assets);
			
			return $tmpCollection;
		}
		function postProcessThumbnail(string $imageBlob): string{
			return ImageLogic::removeUniformBackground($imageBlob,3,3,0.015);
		}
	}
?>