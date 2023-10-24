<?php

	// rawcatalog

	class CreatorFetcher11 extends CreatorFetcher{

		private CREATOR $creator = CREATOR::RAWCATALOG;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();
			$targetUrl = $config['main']['apiUrl'];

			// Parse XML
			
			$sourceData = new SimpleXMLElement(FetchLogic::fetchRemoteData($targetUrl));

			$countAssets = 0;
			foreach ($config['xmlparsing'] as $xPathPrefix => $typeId) {
				
				foreach( $sourceData->xpath("$xPathPrefix//file") as $rawCatalogAsset){

					$url = $rawCatalogAsset->url;

					if($countAssets < $config['main']['maxAssetsPerRound'] && !in_array($url,$existingUrls)){

						$tags = [];
						foreach ($rawCatalogAsset->tags->tag as $t) {
							$tags []= $t;
						}

						$tmpAsset = new Asset(
							url: $rawCatalogAsset->url,
							name: $rawCatalogAsset->name,
							date: $rawCatalogAsset->updated,
							tags: $tags,
							type: TYPE::from($typeId),
							creator: $this->creator,
							license: LICENSE::CUSTOM,
							thumbnailUrl: $rawCatalogAsset->cover
						);

						$tmpCollection->assets []= $tmpAsset;
						$countAssets++;
					}
				}
				
			}

			return $tmpCollection;
		}
	}