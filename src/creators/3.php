<?php

	// sharetextures

	class CreatorFetcher3 extends CreatorFetcher{
		private final int $creatorId = 3;
		function findNewAssets():AssetCollection{

			// Get existing Assets
			$existingUrls = $this->getExistingUrls();

			// Get list of URLs
			$urlArray = FetchLogic::fetchRemoteCommaSeparatedList($this->config["urlList"]);
			
			$tmpCollection = new AssetCollection();

			$countAssets = 0;
			foreach ($urlArray as $url) {
				if(!in_array($url,$existingUrls)){
					$siteContent = FetchLogic::fetchRemoteData($url);
					$metaTags = HtmlLogic::readMetatagsFromHtmlString($siteContent);

					$tmpAsset = new Asset(
						assetName: $metaTags['og:title'],
						url: $url,
						date: $metaTags['tex1:release-date'],
						tags: explode(",",$metaTags['tex1:tags']),
						type:new Type(
							typeId: $this->config['types'][$metaTags['tex1:type']]
						),
						license: new License(
							licenseId: $this->config['licenses'][strtolower($metaTags['tex1:license'])]
						),
						creator: new Creator(
							creatorId: 3
						),
						thumbnailUrl: $metaTags['tex1:preview-image']
					);

					$tmpCollection->assets []= $tmpAsset;
					$countAssets++;
				}
				if($countAssets >= $this->config['maxAssets']){
					break;
				}
			}
			
			return $tmpCollection;
		}
		function postProcessThumbnail(string $imageBlob): string{
			return ImageLogic::removeUniformBackground($imageBlob,25,25,0.015);
		}
	}
?>