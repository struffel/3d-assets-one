<?php

	// chocofur

	class CreatorFetcher9 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::CHOCOFUR;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();

			$pageCounter = 1;
			do{
				$pageContent = FetchLogic::fetchRemoteData($config['apiUrl'].$pageCounter);
				$dom = HtmlLogic::domObjectFromHtmlString($pageContent);
				foreach (HtmlLogic::getElementsByClassName($dom,'item') as $item) {
					$link = $item->getElementsByTagName("a")[0]->getAttribute('href');
					$image = $item->getElementsByTagName("img")[0]->getAttribute("data-src");
					$name = HtmlLogic::getElementsByClassName($dom,"item-description",$item)[0]->textContent;
					$name = trim($name);

					// decide whether model or shader/material
					$isMaterial = preg_match($config['isMaterialRegex'],$link);

					// decide whether asset should be skipped
					$isSkipped = preg_match($config['isSkippedRegex'],$link);

					if(!$isSkipped && !in_array($link,$existingUrls)){
						
						$tmpAsset = new Asset(
							id: NULL,
							name: $name,
							url: $link,
							date: date("Y-m-d"),
							tags: explode(" ",$name),
							type: match($isMaterial){
								true => TYPE::PBR_MATERIAL,
								false => TYPE::MODEL_3D
							},
							creator: $this->creator,
							license: LICENSE::CC0,
							thumbnailUrl: $image,
							quirks: [QUIRK::SIGNUP_REQUIRED]
						);
						
						$tmpCollection->assets []= $tmpAsset;
					}
				}
				$pageCounter++;
			}while(sizeof($dom->getElementsByTagName('button')) == 1);

			return $tmpCollection;
		}
	}