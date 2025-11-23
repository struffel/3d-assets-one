<?php

	// Location Textures

	use Rct567\DomQuery\DomQuery;

	class CreatorFetcher19 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::LOCATION_TEXTURES;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();
			$page = 1;

			$processedAssets = 0;
			$maxAssets = $config['maxAssetsPerRound'];

			do{
				$rawHtml = FetchLogic::fetchRemoteData($config['indexingBaseUrl'].$page);
				if($rawHtml != ""){
					$dom = HtmlLogic::domObjectFromHtmlString($rawHtml);
					$domQuery = new DomQuery($dom);

					$assetLinkElements = $domQuery->find("#product-category a.pack-link");
					$assetsFoundThisIteration = sizeof($assetLinkElements);

					foreach ($assetLinkElements as $assetLinkElement) {

						$assetImageElement = $assetLinkElement->find('img.pack-link-img');

						// use current date as a fallback
						$date =  date("Y-m-d");

						if( !in_array($assetLinkElement->attr('href'),$existingUrls)){

							$detailPageRawHtml = FetchLogic::fetchRemoteData($assetLinkElement->attr('href'));
							$detailPageDom = HtmlLogic::domObjectFromHtmlString($detailPageRawHtml);
							$detailPageDomQuery = new DomQuery($detailPageDom);
							
							// Find tags on detail page

							$tagLinks = $detailPageDomQuery->find("section a[href*='?tag']");
							$tags = [];
							foreach ($tagLinks as $tagLink) {
								$tags []= $tagLink->text();
							}

							$tmpCollection->assets []= new Asset(
								id: NULL,
								name: $assetImageElement->attr('title'),
								url: $assetLinkElement->attr('href'),
								thumbnailUrl: $assetImageElement->attr('data-src'),
								date: $date,
								tags:  array_merge(
									array_filter(
										preg_split('/[^A-Za-z0-9]/',$assetImageElement->attr('title'))
									),
									$tags
								),
								type: TYPE::HDRI,
								license: LICENSE::CUSTOM,
								creator: $this->creator,
								quirks: [],
								status: AssetStatus::PENDING
							);

							$processedAssets += 1;
						}

						if(  $processedAssets >= $maxAssets){
							break;
						}

					}
				}else{
					$assetsFoundThisIteration = 0;
				}
				
				$page += 1;

			}while($assetsFoundThisIteration > 0 && $processedAssets < $maxAssets);

			return $tmpCollection;
		}

	}