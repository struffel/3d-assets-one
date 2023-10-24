<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet();
$assets = AssetLogic::getAssets($query);

?>

<?php if($query->offset == 0){ ?>

	<div id="asset-count" class="asset-count" >Found <?=$assets->totalNumberOfAssetsInBackend?> assets.</div>

<?php } ?>

<?php foreach ($assets->assets as $a) { ?>

	<div class="asset-box">
		<a href="/go?id=<?=$a->id?>" style="z-index: 99;">
			<img title="<?=$a->name?>" class="asset-image" alt="<?=$a->name?>" width="128" height="128" loading="lazy" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/<?=$a->id?>.jpg">
		</a>
	</div>

<?php } ?>

<?php if($assets->hasMoreAssets OR true){ ?>
	<div class="load-more" id="loadMore">
		<button hx-get="/render/asset-list.php?<?=$assets->nextCollection->toHttpGet()?>" hx-trigger="click" hx-swap="outerHTML" hx-target="#loadMore">load more</button>
	</div>
<?php } ?>