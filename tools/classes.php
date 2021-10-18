<?php

class Asset{
	public string $assetId;
	public string $creatorId;
}

abstract class Creator{
	abstract protected function findNewAssets():AssetCollection;
	abstract protected function refreshAssetById(int $assetId):Asset;
}

class AssetCollection{
	public array $assets = array();
	public string $totalNumberOfAssets;
	//public SearchQuery $previousPage;
	public SearchQuery $nextCollection;
}

class AssetQuery{
	
}

?>