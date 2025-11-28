<?php

use asset\AssetLogic;
use asset\AssetQuery;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$query = AssetQuery::fromHttpGet();
$assets = $query->execute();

header("HX-Replace-Url: ?" . $_SERVER['QUERY_STRING']);

?>

<?php if ($query->offset == 0) { ?>

	<div id="asset-count-text">Showing <?= $assets->totalNumberOfAssetsInBackend ?> results</div>

<?php } ?>

<?php $i = 0;
foreach ($assets->assets as $a) { ?>

	<div class="asset-box">
		<a target="_blank" href="/go?id=<?= $a->id ?>">
			<img class="asset-creator-image only-hover" title="<?= $a->creator->name() ?>" width="32" height="32" src="/img/creator/<?= $a->creator->value ?>.png">
			<span class="asset-name only-hover"><?= $a->name ?></span>
			<span class="asset-icons only-hover">
				<?php foreach ($a->quirks as $q) { ?>
					<span title="<?= $q->name() ?>"><img src="/svg/quirk/<?= $q->value ?>.svg" width="32" height="32"></span>
				<?php } ?>
				<span title="<?= $a->license->name() ?>"><img src="/svg/license/<?= $a->license->value ?>.svg" width="32" height="32"></span>
			</span>
			<img class="asset-image" alt="<?= $a->name ?>" src="<?= getenv("3D1_CDN") ?>/thumbnail/256-JPG-FFFFFF/<?= $a->id ?>.jpg">
		</a>
	</div>

<?php } ?>

<?php if ($assets->nextCollection != NULL) { ?>
	<div style="opacity:0;transform:translateY(-650px);" id="load-more" hx-get="/render/asset-list.php?<?= $assets->nextCollection->toHttpGet() ?>" hx-trigger="intersect once" hx-swap="outerHTML"></div>
<?php } else { ?>
	<div id="end-reached" class="results-end-text">
		End of results.
	</div>
<?php } ?>