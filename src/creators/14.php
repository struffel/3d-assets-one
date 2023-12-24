<?php

	use Rct567\DomQuery\DomQuery;

	// poliigon

	class CreatorFetcher14 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::POLIIGON;

		private function extractId($url){
			return end(explode('/', rtrim($url, '/')));
		}

		private function isInExistingUrls($url, $existingUrls) {
			// Extracting ID from the input link
			
			$id = $this->extractId($url);
			$existingIds = [];
			foreach ($existingUrls as $eU) {
				$existingIds []= $this->extractId($eU);
			}
		
			return in_array($id,$existingIds);
		}

		function validateAsset(Asset $asset): bool {
			try{
				$rawHtml = FetchLogic::fetchRemoteData($asset->url);

				$isFree = stripos($rawHtml,"isfree:c");
				return $isFree;
			}catch(Throwable $e){
				return false;
			}
			
		}

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();

			$page = 1;

			do{
				$rawHtml = FetchLogic::fetchRemoteData($config['searchBaseUrl'].$page);
				$dom = HtmlLogic::domObjectFromHtmlString($rawHtml);
				$domQuery = new DomQuery($dom);

				$assetBoxDomElements = $domQuery->find('a.asset-box');
				$assetBoxesFoundThisIteration = sizeof($assetBoxDomElements);
				foreach ($assetBoxDomElements as $assetBox) {

					$urlPath = $assetBox->attr('href');
					$url = $config['baseUrl'].$urlPath;

					if(!$this->isInExistingUrls($url,$existingUrls)){

						$name = $assetBox->find('img')->attr('alt');

						$type = NULL;

						foreach ($config['urlTypeRegex'] as $regex => $typeId) {
							if(preg_match($regex,$urlPath)){
								$type = TYPE::from($typeId);
							}
						}
						if(!$type){
							throw new Exception("Could not find type from urlPath '$urlPath'");
						}

						$tmpCollection->assets []= new Asset(
							id: NULL,
							name:$name,
							url: $url,
							thumbnailUrl: $assetBox->find('img')->attr('src'),
							date: date("Y-m-d"),
							tags: preg_split('/\s|,/',$name),
							type: $type,
							license: LICENSE::CUSTOM,
							creator: $this->creator,
							quirks: [QUIRK::SIGNUP_REQUIRED],
							status: ASSET_STATUS::PENDING
						);
					}
				}

				$page += 1;

			}while($assetBoxesFoundThisIteration > 0 && $page < 20 /* Failsafe */);

			return $tmpCollection;
		}
	}