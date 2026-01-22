<?php

use asset\StoredAssetQuery;
use asset\StoredAsset;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$query = StoredAssetQuery::fromHttpGet();
$assets = $query->execute();

header("HX-Replace-Url: ?" . $_SERVER['QUERY_STRING']);

?>

<?php if ($query->offset == 0) { ?>

	<div id="asset-count-text">Showing ... results</div>

<?php } ?>

<?php $i = 0;
/**
 * @var StoredAsset $a */
foreach ($assets as $a) { ?>

	<div class="asset-box">
		<a target="_blank" href="/go?id=<?= $a->id ?>">
			<img class="asset-creator-image only-hover" title="<?= $a->creator->title() ?>" width="32" height="32" src="/img/static/creator/<?= $a->creator->value ?>.png">
			<span class="asset-name only-hover"><?= $a->title ?></span>
			<span class="asset-icons only-hover">
				<?php
				//<span title="<?= $a->creator->commonLicense()->name() ?? "" "><img src="/svg/license/<?= $a->creator->commonLicense()->value .svg" width="32" height="32"></span>
				?>
			</span>
			<img class="asset-image" alt="<?= $a->title ?>" src="<?= $a->getThumbnailUrl(256, "JPG", "FFFFFF") ?>">
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