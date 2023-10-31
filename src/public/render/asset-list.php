<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet();
$assets = AssetLogic::getAssets($query);

header("HX-Replace-Url: ?".$_SERVER['QUERY_STRING']);

?>

<?php if($query->offset == 0){ ?>

	<!--<div id="asset-count-text" >Found <?=$assets->totalNumberOfAssetsInBackend?> assets.</div>-->

<?php } ?>

<?php foreach ($assets->assets as $a) { ?>

	<div class="asset-box">
		<a href="/go?id=<?=$a->id?>" >
			<img class="asset-creator-image" title="<?=$a->creator->name()?>" width="24" height="24" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/creator-icon/64-PNG/<?=$a->creator->value?>.png">
			<span class="asset-name"><?=$a->name?></span>
			<span class="asset-icons">
				<!--<?php foreach($a->quirks as $q){ ?>
					<span><?=$q->value?></span>
				<?php } ?>-->
				<span><?=strtoupper($a->license->slug())?></span>
			</span>
			<img class="asset-image" alt="<?=$a->name?>" loading="lazy" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/<?=$a->id?>.jpg">
		</a>
	</div>

<?php } ?>

<?php if($assets->nextCollection != NULL){ ?>
	<div style="opacity:0;transform:translateY(-650px);" id="load-more" hx-get="/render/asset-list.php?<?=$assets->nextCollection->toHttpGet()?>" hx-trigger="intersect once" hx-swap="outerHTML" ></div>
<?php }else{ ?>
	<div id="end-reached" class="results-end-text">
		End of results.
	</div>
<?php } ?>