<?php

	// sharetextures

	class CreatorFetcher3 extends CreatorFetcher{
		private CREATOR $creator = CREATOR::SHARETEXTURES;
		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			// Get list of URLs
			$urlArray = FetchLogic::fetchRemoteCommaSeparatedList($config["urlList"]);
			
			$tmpCollection = new AssetCollection();

			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){
					$siteContent = FetchLogic::fetchRemoteData($url);
					$metaTags = HtmlLogic::readMetatagsFromHtmlString($siteContent);

					$tmpAsset = new Asset(
						name: $metaTags['og:title'],
						url: $url,
						date: $metaTags['tex1:release-date'],
						tags: explode(",",$metaTags['tex1:tags']),
						type: TYPE::from($config['types'][$metaTags['tex1:type']]),
						license: LICENSE::from($config['licenses'][strtolower($metaTags['tex1:license'])]),
						creator: $this->creator,
						thumbnailUrl: $metaTags['tex1:preview-image'],
						quirks: [QUIRK::ADS]
					);

					$tmpCollection->assets []= $tmpAsset;
					$countAssets++;
				}
				if($countAssets >= $config['maxAssets']){
					break;
				}
			}
			
			return $tmpCollection;
		}
		
		public function fetchThumbnailImage(string $url):string {
			return ImageLogic::removeUniformBackground(FetchLogic::fetchRemoteData($url),25,25,0.015);
		}
	}