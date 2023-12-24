<?php

	// textures.com

	class CreatorFetcher15 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::TEXTURES_COM;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();

			$page = 1;

			do{
				$apiData = FetchLogic::fetchRemoteJson($config['apiUrlBase'].$page);

				$assetsFoundThisIteration = sizeof($apiData['data']);
				foreach ($apiData['data'] as $texComAsset) {

					$url = "https://textures.com/download/".$texComAsset['filenameWithoutSet']."/".$texComAsset['defaultPhotoSet']['id'];

					if(!in_array($url,$existingUrls)){

						$tmpCollection->assets []= new Asset(
							id: NULL,
							name:$texComAsset['defaultPhotoSet']['titleThumbnail'],
							url: $url,
							thumbnailUrl: "https://textures.com/".$texComAsset['picture'],
							date: $texComAsset['defaultPhotoSet']['createdAtUtc'],
							tags: array_filter(preg_split('/[^A-Za-z0-9]/',$texComAsset['defaultPhotoSet']['titleThumbnail'])),
							type: TYPE::from($config['categoryMapping'][$texComAsset['defaultCategoryId']]),
							license: LICENSE::CUSTOM,
							creator: $this->creator,
							quirks: [QUIRK::SIGNUP_REQUIRED],
							status: ASSET_STATUS::PENDING
						);
					}
				}

				$page += 1;

			}while($assetsFoundThisIteration > 0 && $page < 20 /* Failsafe */);

			return $tmpCollection;
		}

	}