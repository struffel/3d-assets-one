<?php

	// CGMood

	use Rct567\DomQuery\DomQuery;

	class CreatorFetcher16 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::CGMOOD;

		function validateAsset(Asset $asset): bool {
			$rawHtml = FetchLogic::fetchRemoteData($asset->url);

			$dom = HtmlLogic::domObjectFromHtmlString($rawHtml);
			$domQuery = new DomQuery($dom);

			$downloadButtonCandidates = $domQuery->find('.download-button');
			
			if(sizeof($downloadButtonCandidates) > 0){
				$downloadButton = $downloadButtonCandidates[0];
				return preg_match('/.*Free download.*/',$downloadButton->text());
			}else{
				return false;
			}
			
		}

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();

			$page = $this->getFetchingState("page") ?? 1;
			$pagesProcessed = 0;

			do{
				$attempts = 0;
				$rawHtml = "";
				while(!$rawHtml){
					try {
						$rawHtml = FetchLogic::fetchRemoteData($config['indexingBaseUrl'].$page);
					} catch (\Throwable $th) {
						Log::write("Failed to load site. Attempt: $attempts","WARN");
						sleep($attempts*2);
						$attempts = $attempts + 1;

						if($attempts > 4){
							throw new Exception("Failed to load site, even after multiple attempts.");
						}
					}
				}
				
				$dom = HtmlLogic::domObjectFromHtmlString($rawHtml);
				$domQuery = new DomQuery($dom);

				$assetImageElements = $domQuery->find('.product img');
				$assetsFoundThisIteration = sizeof($assetImageElements);

				foreach ($assetImageElements as $assetImageElement) {

					$type = NULL;

					foreach ($config['urlTypeRegex'] as $regex => $typeId) {
						if(preg_match($regex,$assetImageElement->attr('data-product-url'))){
							$type = TYPE::from($typeId);
						}
					}
					if(!$type){
						Log::write("Skipping ".$assetImageElement->attr('data-product-url')." because it does not match the URL schema.");
					} 
					elseif (!in_array($assetImageElement->attr('data-product-url'),$existingUrls)){

						$tmpCollection->assets []= new Asset(
							id: NULL,
							name: $assetImageElement->attr('data-product-title'),
							url: $assetImageElement->attr('data-product-url'),
							thumbnailUrl: "https://cgmood.com".$assetImageElement->attr('src'),
							date: date("Y-m-d"),
							tags: array_filter(preg_split('/[^A-Za-z0-9]/',$assetImageElement->attr('data-product-title'))),
							type: $type,
							license: LICENSE::CUSTOM,
							creator: $this->creator,
							quirks: [QUIRK::SIGNUP_REQUIRED,QUIRK::LIMITED_FREE_DOWNLOADS],
							status: AssetStatus::PENDING
						);

					}
				}

				$page += 1;
				$pagesProcessed += 1;

				if($assetsFoundThisIteration < 1){
					$page = 1;
				}

			}while($pagesProcessed < $config['maxPagesPerIteration']);

			$this->saveFetchingState("page",$page);

			return $tmpCollection;
		}

	}