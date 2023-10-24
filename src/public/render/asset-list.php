<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet();
$assets = AssetLogic::getAssets($query);

?>

<?php if($query->offset == 0){ ?>

	<div id="asset-count">Found <?=$assets->totalNumberOfAssetsInBackend?> assets.</div>

<?php } ?>

<?php foreach ($assets->assets as $a) { ?>

	<div>
		<a href="/go?id=<?=$a->id?>" class="scaleHover scaleHoverStrong box is-clipped is-bordered mx-1 my-1" style="z-index: 99;">
			<figure class="image is-128x128">
				<img onload="this.style.opacity = '100%'" alt="<?=$a->name?>" width="256" height="256" loading="lazy" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/<?=$a->id?>.jpg" style="opacity: 1;">
			</figure>
		</a>
	</div>

<?php } ?>

<?php if($assets->hasMoreAssets){ ?>
	<button  >load more</button>
<?php } ?>