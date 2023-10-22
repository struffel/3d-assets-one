<?php

	// pbrmaterials.com

	class CreatorFetcher13 extends CreatorFetcher{

		private CREATOR $creator = CREATOR::PBRMATERIALS;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();

			$page = 1;
			$wpOutput=[];
			$fetchedList = [];
			$continue = true;

			$processedAssets = 0;
			$maxAssets = $config['maxAssetsPerRound'];

			while($continue){
				$wpLink=$config['apiUrl']."product?_embed&per_page=100&page=$page&orderby=date";
				$wpOutput=FetchLogic::fetchRemoteJson($wpLink);
				if($wpOutput){
					$page++;

					foreach($wpOutput as $wpPost){
						if(!in_array($wpPost['link'],$existingUrls)){

							// Tags
							$tmpTags = [];
							foreach ($wpPost['_embedded']['wp:term'] as $wpTermCategory) {
								// embeddedTerm = Distinction between "Tags" or "Category"
								// Both will be treated as tags
								foreach ($wpTermCategory as $taglikeObject) {
									$tmpTags []= $taglikeObject['name'];
								}
							}

							// Thumbnail
							$thumbnailUrl = "";
							$oldErrorReportingLevel = error_reporting();
							error_reporting(E_ERROR | E_PARSE);

							// 1st attempt
							try{
								$thumbnailUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['large']['s3']['url'];
							}catch(Throwable $e){
								LogLogic::write($e->getMessage()." / 1st attempt failed... / ".$wpPost['link'],"IMG-ERROR");
							}

							// 2nd attempt
							if(!isset($thumbnailUrl)){
								try{
									$thumbnailUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
								}catch(Throwable $e){
									LogLogic::write($e->getMessage()." / 2nd attempt failed... / ".$wpPost['link'],"IMG-ERROR");
								}
							}

							error_reporting($oldErrorReportingLevel);

							// Test if any attempt worked
							if(!isset($thumbnailUrl)){
								continue;
							}

							$tmpAsset = new Asset(
								name: $wpPost['title']['rendered'],
								url: $wpPost['link'],
								date: $wpPost['date'],
								tags: $tmpTags,
								thumbnailUrl: $thumbnailUrl,
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

				}else{
					$continue = false;
				}
			}

			return $tmpCollection;
		}

		function fetchThumbnailImage(string $url): string{
			return FetchLogic::fetchRemoteData($url,["referer" => "https://pbrmaterial.com/"]);
		}
	}
?>