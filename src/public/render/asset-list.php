<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet();
$assets = AssetLogic::getAssets($query);

?>

<?php if($query->offset == 0){ ?>

	<div id="asset-count-text" >Found <?=$assets->totalNumberOfAssetsInBackend?> assets.</div>

<?php } ?>

<?php foreach ($assets->assets as $a) { ?>

	<div class="asset-box">
		<a href="/go?id=<?=$a->id?>" onmouseover="">
			<img title="<?=$a->name?>" class="asset-image" alt="<?=$a->name?>" width="192" height="192" loading="lazy" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/<?=$a->id?>.jpg">
		</a>
	</div>

<?php } ?>

<?php if($assets->nextCollection != NULL){ ?>
	<div style="opacity:0;transform:translateY(-500px);" id="load-more" hx-get="/render/asset-list.php?<?=$assets->nextCollection->toHttpGet()?>" hx-trigger="intersect once" hx-swap="outerHTML" ></div>
<?php }else{ ?>
	<div id="end-reached" class="results-end-text">
		End of results.
	</div>
<?php } ?>