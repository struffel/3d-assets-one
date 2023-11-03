<?php

	// texturecan

	class CreatorFetcher6 extends CreatorFetcher{

		public CREATOR $creator = CREATOR::TEXTURECAN;

		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$urlArray = FetchLogic::fetchRemoteCommaSeparatedList($config['urlList']);

			$tmpCollection = new AssetCollection();

			$maxAssets = 5;
			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){
					$metaTags = HtmlLogic::readMetatagsFromHtmlString(FetchLogic::fetchRemoteData($url));

					$tmpAsset = new Asset(
						id: NULL,
						name: $metaTags['tex1:name'],
						url: $url,
						date: $metaTags['tex1:release-date'],
						tags: StringLogic::explodeFilterTrim(",",$metaTags['tex1:tags']),
						type: TYPE::from($config['types'][$metaTags['tex1:type']]),
						license: LICENSE::from($config['licenses'][$metaTags['tex1:license']]),
						creator: $this->creator,
						thumbnailUrl: $metaTags['tex1:preview-image'],
						quirks: [QUIRK::ADS]
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