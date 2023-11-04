<?php

	use Rct567\DomQuery\DomQuery;

	// poliigon

	class CreatorFetcher14 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::POLIIGON;

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

					if(!in_array($url,$existingUrls)){

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
							tags: preg_split('/\s/',$name),
							type: $type,
							license: LICENSE::CUSTOM,
							creator: $this->creator,
							quirks: [QUIRK::SIGNUP_REQUIRED],
							status: ASSET_STATUS::INACTIVE
						);
					}
				}

				$page += 1;

			}while($assetBoxesFoundThisIteration > 0 && $page < 20 /* Failsafe */);

			//LogLogic::write(json_encode($tmpCollection->assets,JSON_PRETTY_PRINT));

			return $tmpCollection;
		}
	}