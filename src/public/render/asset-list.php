<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet();
$assets = AssetLogic::getAssets($query);

header("HX-Replace-Url: ?".$_SERVER['QUERY_STRING']);

?>

<?php if($query->offset == 0){ ?>

	<style>
		main{
		display:grid;
		grid-template-columns: repeat(auto-fit, minmax(var(--asset-box-size), max-content));
		place-items: center;
		place-content: center;
		margin-left: 50px;
		margin-right: 50px;
		z-index: 1;

		.asset-box{
			display: inline-block;
			transition: transform ease-in-out 0.1s,opacity ease-in-out .2s;
			width:var(--asset-box-size);
			height: var(--asset-box-size);
			border: 1px solid black;
			background-color: white;

			position: relative;

			.asset-image{
				padding:5px;
				width: 100%;
				height: 100%;
			}

			.asset-creator-image{
				position:absolute;
				top: 0;
				left: 0;
				opacity: 0;
			}

			.asset-name{
				position:absolute;
				bottom: 0;
				right: 0;
				opacity: 0;
				padding:2px;
				text-align: right;
				background-color: palegreen;
				max-width: 80%;
			}

			.asset-quirks{
				position: absolute;
				bottom:0;
				left:0;
				padding: 2px;
				background-color: palegoldenrod;
			}

		}

		.asset-box:hover{
			transform: scale(1.05);
			z-index: 2;

			.asset-name{
				opacity: 1;
			}

			.asset-creator-image{
				opacity: 1;
			}
		}

		

		#asset-count-text{
			width: 100%;
			text-align: center;
			margin: 5px;
			grid-column: 1/-1;
		}

		.results-end-text{
			width: 100%;
			text-align: center;
			grid-column: 1/-1;
		}
	}
	</style>
	<div id="asset-count-text" >Found <?=$assets->totalNumberOfAssetsInBackend?> assets.</div>

<?php } ?>

<?php foreach ($assets->assets as $a) { ?>

	<div class="asset-box">
		<a href="/go?id=<?=$a->id?>" >
			<img class="asset-creator-image" width="24" height="24" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/creator-icon/64-PNG/<?=$a->creator->value?>.png">
			<span class="asset-name"><?=$a->name?></span>
			<span class="asset-quirks">
				<?php foreach($a->quirks as $q){ ?>
					<span><?=$q->value?></span>
				<?php } ?>
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