<?php

use asset\StoredAssetQuery;
use asset\ScrapedAssetStatus;
use asset\CommonLicense;

use asset\AssetType;
use blocks\EditorRow;
use misc\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

Auth::requireAuth();
header('Cache-Control: no-store');


header("HX-Replace-Url: ?" . $_SERVER['QUERY_STRING']);

$query = StoredAssetQuery::fromHttpGet(filterStatus: NULL);
$assets = $query->execute();

// Render each asset
foreach ($assets as $a) {
	EditorRow::render($a);
}

if ($assets->nextCollection != NULL) { ?>
	<div style="opacity:0;transform:translateY(-650px);" id="load-more" hx-get="/admin/render/editor-list.php?<?= $assets->nextCollection->toHttpGet() ?>" hx-trigger="intersect once" hx-swap="outerHTML"></div>
<?php } else { ?>
	<div id="end-reached" class="results-end-text">
		End of results.
	</div>
<?php } ?>