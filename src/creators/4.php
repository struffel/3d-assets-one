<?php

	// 3dtextures

	class CreatorFetcher4 extends CreatorFetcher{

		private CREATOR $creator = CREATOR::THREE_D_TEXTURES;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{
			
			$tmpCollection = new AssetCollection();

			$page = 1;
			$wpOutput=[];

			$processedAssets = 0;
			$maxAssets = $config['maxAssetsPerRound'];

			$continue = true;
			do{
				$wpLink=$config['apiUrl']."posts?_embed&per_page=100&page=$page&orderby=date";
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
								LogLogic::write($e->getMessage()." / 1st attempt failed... / ".$wpPost['link'],"IMG-ERROR");
							}

							// 2nd attempt
							if(!isset($tmpThumbnail)){
								try{
									$tmpThumbnail = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
								}catch(Throwable $e){
									LogLogic::write($e->getMessage()." / 2nd attempt failed... / ".$wpPost['link'],"IMG-ERROR");
								}
							}

							// 3rd attempt
							if(!isset($tmpThumbnail)){
								try{
									$tmpThumbnail = $wpPost['jetpack_featured_media_url'];
								}catch(Throwable $e){
									LogLogic::write($e->getMessage()." / 3rd attempt failed... / ".$wpPost['link'],"IMG-ERROR");
								}
							}

							error_reporting($oldErrorReportingLevel);

							// Test if any attempt worked
							if(!isset($tmpThumbnail)){
								LogLogic::write("All attempts failed. Thumbnail could not be resolved. Skipping... / ".$wpPost['link'],"IMG-ERROR");
								continue;
							}
							
							// Assemble asset
							$tmpAsset = new Asset(
								name: $wpPost['title']['rendered'],
								url: $wpPost['link'],
								date: $wpPost['date'],
								tags: $tmpTags,
								thumbnailUrl: $tmpThumbnail,
								type: TYPE::PBR_MATERIAL,
								license: LICENSE::CC0,
								creator: $this->creator

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
			
			return $tmpCollection;
		}
		public function fetchThumbnailImage(string $url):string {
			return ImageLogic::removeUniformBackground(FetchLogic::fetchRemoteData($url),3,3,0.015);
		}
	}