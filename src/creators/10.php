<?php

	// amd materialx

	class CreatorFetcher10 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::GPUOPENMATLIB;

		public function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();
			$targetUrl = $config['apiUrl'];
			// Limit number of assets to avoid excessive calls to the tag API
			$countAssets = 0;
			do{
				$apiJson = FetchLogic::fetchRemoteJson($targetUrl);
				foreach ($apiJson['results'] as $amdAsset) {
					if($countAssets < $config['maxAssetsPerRound'] && !preg_match($config['excludeTitleRegex'],$amdAsset['title'])){

						$url = str_replace('#ID#',$amdAsset['id'],$config['urlTemplate']);

						if(!in_array($url,$existingUrls)){

							$tags = [];

							foreach ($amdAsset['tags'] as $t) {
								$tags []= FetchLogic::fetchRemoteJson($config['tagApiUrl'].$t)['title'];
							}

							$tmpAsset = new Asset(
								id: NULL,
								url: $url,
								name: $amdAsset['title'],
								date: $amdAsset['published_date'],
								tags: $tags,
								type: TYPE::PBR_MATERIAL,
								license: LICENSE::APACHE_2_0,
								creator: $this->creator,
								thumbnailUrl: str_replace('#ID#',$amdAsset['renders_order'][0],$config['previewImageTemplate'])
							);
							
							$tmpCollection->assets []= $tmpAsset;
							$countAssets++;
						}
						
					}
				}
				$targetUrl = $apiJson['next']??null;
			}while($targetUrl != null && $countAssets < $config['maxAssetsPerRound']);

			return $tmpCollection;
		}

		public function fetchThumbnailImage(string $url):string {
			return ImageLogic::removeUniformBackground(FetchLogic::fetchRemoteData($url),10,10,0.015);
		}

	}