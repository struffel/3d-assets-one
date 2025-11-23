<?php

	// Three D Scans

	use Rct567\DomQuery\DomQuery;

	class CreatorFetcher18 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::THREE_D_SCANS;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();
			$page = 1;

			do{
				$rawHtml = FetchLogic::fetchRemoteData($config['indexingBaseUrl'].$page);
				if($rawHtml != ""){
					$dom = HtmlLogic::domObjectFromHtmlString($rawHtml);
					$domQuery = new DomQuery($dom);

					$assetLinkElements = $domQuery->find('article a');
					$assetsFoundThisIteration = sizeof($assetLinkElements);

					foreach ($assetLinkElements as $assetLinkElement) {

						$assetImageElement = $assetLinkElement->find('img.frontPageImg');

						// Extract year and month from thumbnail URL or use current date as a fallback
						preg_match('/[0-9]{4}\/[0-9]{2}/', $assetImageElement->attr('src'), $matches);
						$date = isset($matches[0]) ? str_replace('/','-',$matches[0])."-01" : date("Y-m-d");

						if( !in_array($assetLinkElement->attr('href'),$existingUrls)){
							$tmpCollection->assets []= new Asset(
								id: NULL,
								name: $assetLinkElement->attr('title'),
								url: $assetLinkElement->attr('href'),
								thumbnailUrl: $assetImageElement->attr('src'),
								date: $date,
								tags:  array_merge(array_filter(preg_split('/[^A-Za-z0-9]/',$assetLinkElement->attr('title'))),['statue','sculpture']),
								type: TYPE::MODEL_3D,
								license: LICENSE::CC0,
								creator: $this->creator,
								quirks: [],
								status: AssetStatus::PENDING
							);
						}

					}
				}else{
					$assetsFoundThisIteration = 0;
				}
				
				$page += 1;

			}while($assetsFoundThisIteration > 0);

			return $tmpCollection;
		}

	}