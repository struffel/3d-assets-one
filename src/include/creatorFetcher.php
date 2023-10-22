<?php

abstract class CreatorFetcher{

	// Class variables
	private CREATOR $creator;

	// General functions
	public final function runUpdate() : AssetCollection{

		// Ensure that creator is set
		if(!$this->creator){
			throw new Exception("Creator not set.", 1);
		}

		// Get existing URLs
		$query = new AssetQuery();
		$query->filterCreator = [$this->creator];
		$query->filterActive = NULL;
		$result = AssetLogic::getAssets($query);
		$existingUrls = [""];
		foreach ($result->assets as $asset) {
			$existingUrls []= $asset->url;
		}

		// Get new assets using creator-specific method
		// Passing in the list of existing URLs and
		$newAssetCollection = $this->findNewAssets($existingUrls,json_decode(file_get_contents($this->creator->value.".json"),true));

		// Perform post-processing on the results
		for ($i=0; $i < sizeof($newAssetCollection->assets); $i++) { 
			// Expand and clean up the tag array
			$newAssetCollection->assets[$i]->tags = array_merge($newAssetCollection->assets[$i]->tags,preg_split('/\s+/', $newAssetCollection->assets[$i]->name));
			$newAssetCollection->assets[$i]->tags []= $this->creator->slug();
			$newAssetCollection->assets[$i]->tags = AssetLogic::filterTagArray($newAssetCollection->assets[$i]->tags);
		}

		return $newAssetCollection;

	}

	// Creator-specific functions

	public function fetchThumbnailImage(string $url):string {
		return FetchLogic::fetchRemoteData($url);
	}
	public abstract function findNewAssets(array $existingUrls, array $config):AssetCollection;
}