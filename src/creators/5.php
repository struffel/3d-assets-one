<?php

	// cgbookcase

	class CreatorFetcher5 extends CreatorFetcher{

		private CREATOR $creator = CREATOR::CGBOOKCASE;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{
			
			$urlArray = FetchLogic::fetchRemoteCommaSeparatedList($config['urlList']);

			$tmpCollection = new AssetCollection();

			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){

					$siteContent = FetchLogic::fetchRemoteData($url);
					$metaTags = HtmlLogic::readMetatagsFromHtmlString($siteContent);
					
					$tmpAsset = new Asset(
						name: $metaTags['tex1:name'],
						url: $url,
						date: $metaTags['tex1:release-date'],
						tags: StringLogic::explodeFilterTrim(",",$metaTags['tex1:tags']),
						type: TYPE::from($config['types'][$metaTags['tex1:type']]),
						license: LICENSE::from($config['licenses'][$metaTags['tex1:license']]),
						creator: $this->creator,
						thumbnailUrl: $metaTags['tex1:preview-image']
					);

					$tmpCollection->assets []= $tmpAsset;

					$countAssets++;
					if($countAssets >= $config['maxAssets']){
						break;
					}
				}
			}
			
			

			return $tmpCollection;
		}

		public function fetchThumbnailImage(string $url):string {
			return ImageLogic::removeUniformBackground(FetchLogic::fetchRemoteData($url),2,2,0.015);
		}
	}