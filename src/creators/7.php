<?php

	// noemotionshdr

	class CreatorFetcher7 extends CreatorFetcher{
		function findNewAssets(array $existingUrls, array $config):AssetCollection{

			$tmpCollection = new AssetCollection();

			foreach ($config['urlList'] as $url) {

				if(!in_array($url,$existingUrls)){

					$category = ucfirst(substr(pathinfo($url)['filename'],3));
					$name = explode("=",$url)[1];

					$tmpAsset = new Asset(
						name: $name,
						date: "2010-".preg_split('/=|_/',$url)[1],
						thumbnailUrl: "http://noemotionhdrs.net/Previews/772x386/$category/$name.jpg",
						url: $url,
						tags: ['Sky',$category],
						type: type::HDRI,
						creator: CREATOR::NOEMOTIONHDRS,
						license: LICENSE::CC_BY_ND,
						
					);

					$tmpCollection->assets []= $tmpAsset;
				}
			}
			
			return $tmpCollection;
		}
	}