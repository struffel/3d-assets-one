<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet();
$query->includeQuirks = true;
$assets = AssetLogic::getAssets($query);

header("HX-Replace-Url: ?".$_SERVER['QUERY_STRING']);

?>

<?php if($query->offset == 0){ ?>

	<div id="asset-count-text" >Showing <?=$assets->totalNumberOfAssetsInBackend?> results</div>

<?php } ?>

<?php $i = 0; foreach ($assets->assets as $a) { ?>

	<div class="asset-box">
		<a target="_blank" href="/go?id=<?=$a->id?>" >
			<img class="asset-creator-image only-hover" title="<?=$a->creator->name()?>" width="24" height="24" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/creator-icon/64-PNG/<?=$a->creator->value?>.png">
			<span class="asset-name only-hover"><?=$a->name?></span>
			<span class="asset-icons only-hover">
				<?php foreach($a->quirks as $q){ ?>
					<span title="<?=$q->name()?>"><img src="/svg/quirk/<?=$q->value?>.svg" width="24" height="24"></span>
				<?php } ?>
				<span><?=match ($a->license) {
					LICENSE::CC_BY_ND => "<span title='CC-BY-ND License'><img src='/svg/license/cc.svg'  height='24'></span><span title='CC-BY-ND License'><img src='/svg/license/by.svg'  height='24'></span><span title='CC-BY-ND License'><img src='/svg/license/nd.svg'  height='24'></span>",
					LICENSE::CC0 => "<span title='CC0 License'><img src='/svg/license/cc.svg'  height='24'></span><span title='CC0 License'><img src='/svg/license/zero.svg'  height='24'></span>",
					LICENSE::APACHE_2_0 => "<span title='Apache 2.0 License'><img src='/svg/license/apache.svg'  height='24'></span>",
					default => "<span title='Custom License' ><img src='/svg/license/custom.svg'  height='24'></span>"
				}?></span>
			</span>
			<img class="asset-image" alt="<?=$a->name?>" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/<?=$a->id?>.jpg">
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