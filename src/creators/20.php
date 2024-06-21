<?php

	// PBR PX

	class CreatorFetcher20 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::PBR_PX;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();
			$page = 1;

			$processedAssets = 0;
			$maxAssets = $config['maxAssetsPerRound'];

			do{
				$assetsFoundThisIteration = 0;
				$assetListBody = ['page_number' => $page];
				$assetListRaw = FetchLogic::fetchRemoteJson(url:$config['indexingBaseUrl'],method:'POST',body:json_encode($assetListBody),jsonContentTypeHeader:true);

				$assetList = $assetListRaw['data']['data'];

				foreach ($assetList as $pbrPxAsset) {

					$assetsFoundThisIteration += 1;

					$assetUrl = $config['viewPageBaseUrl'].$pbrPxAsset['id'];

					if(!in_array($assetUrl,$existingUrls)){
						// Fetch asset details
						$assetDetailsBody = ['asset' => $pbrPxAsset['id']];
						$pbrPxAssetDetailsRaw = FetchLogic::fetchRemoteJson(url:$config['assetBaseUrl'],method:'POST',body:json_encode($assetDetailsBody),jsonContentTypeHeader:true);

						//LogLogic::write(print_r($pbrPxAssetDetailsRaw));

						$pbrPxAssetDetails = $pbrPxAssetDetailsRaw['data'][0];

						// Extract information from response
						$tags = array_filter(preg_split('/[^A-Za-z0-9]/',$pbrPxAssetDetails['tag']));

						$type = TYPE::OTHER;
						if( str_starts_with($pbrPxAssetDetails['zips'],"HDRI")){
							$type = TYPE::HDRI;
						}elseif (str_starts_with($pbrPxAssetDetails['zips'],"Textures")) {
							$type = TYPE::PBR_MATERIAL;
						}elseif (str_starts_with($pbrPxAssetDetails['zips'],"3D_Model")) {
							$type = TYPE::MODEL_3D;
						}

						// Decide on thumbnail
						$thumbnailUrlCandidates = explode("+",$pbrPxAssetDetails['img_url']);
						$thumbnailUrl = $config['mediaBaseUrl'].$thumbnailUrlCandidates[0];
						foreach ($thumbnailUrlCandidates as $t) {
							if(str_contains($t,$config['thumbnailIdentifierString'])){
								$thumbnailUrl = $config['mediaBaseUrl'].$t;
							}
						}
						
						// Build asset
						$tmpCollection->assets []= new Asset(
							id: NULL,
							name: $pbrPxAsset['ename'],
							url: $assetUrl,
							thumbnailUrl: $thumbnailUrl,
							date: $pbrPxAsset['create_time'],
							tags: $tags,
							type: $type,
							license: LICENSE::CC0,
							creator: $this->creator,
							quirks: [],
							status: ASSET_STATUS::PENDING
						);

						$processedAssets += 1;
						if($processedAssets > $maxAssets){
							break;
						}

					}

				}

				$page += 1;

			}while($assetsFoundThisIteration > 0 && $processedAssets < $maxAssets);

			return $tmpCollection;
		}

	}