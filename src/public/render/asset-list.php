<?php

use asset\StoredAssetQuery;
use asset\StoredAsset;
use blocks\LogoBlock;
use creator\Creator;
use thumbnail\ThumbnailFormat;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$query = StoredAssetQuery::fromHttpGet();
$assets = $query->execute();

$assetCount = StoredAssetQuery::assetCountTotal();

header("HX-Replace-Url: ?" . $_SERVER['QUERY_STRING']);

if ($query->filterAssetId == [] && $query->filterCreator == [] && $query->filterType == null && $query->offset == 0 && $query->filterTag == []) { ?>
	<div id="welcome-message">
		<div>
			<p>
				Welcome to <strong>3Dassets.one</strong>, a search engine for finding free, high-quality, human-made 3D resources,
				currently listing <strong><?= $assetCount ?> assets</strong> from <strong><?= sizeof(Creator::cases()) ?> sources</strong>.
			</p>
			<p>
				This is a side project to <a class="subtle-link" href="https://ambientCG.com">ambientCG</a>, the free texture site.
				You can support the development via the <a class="subtle-link" href="https://patreon.com/ambientCG">ambientCG Patreon</a>.
				Suggestions for new creators to be listed can be made via <a class="subtle-link" href="https://github.com/struffel/3d-assets-one/issues">GitHub</a>.
			</p>
		</div>
	</div>
<?php }

/**
 * @var StoredAsset $a */
foreach ($assets as $a) { ?>

	<div class="asset-box">
		<a target="_blank" href="/go?id=<?= $a->id ?>">
			<img class="asset-creator-image only-hover" title="<?= $a->creator->title() ?>" width="32" height="32" src="/static/creator/<?= $a->creator->value ?>.png">
			<span class="asset-name only-hover">
				<?= $a->title ?>
			</span>

			<img class="asset-image" alt="<?= $a->title ?>" src="<?= $a->getThumbnailUrl(ThumbnailFormat::JPG_256_FFFFFF) ?>">
		</a>
		<span class="asset-icons only-hover">
			<?php if ($a->creator->licenseUrl() !== null) { ?>
				<a href="<?= $a->creator->licenseUrl() ?>" title="License Details">
					<img src="/static/svg/scale-balance.svg" width="24" height="24" alt="License Details">
				</a>
			<?php } ?>
		</span>
	</div>

<?php } ?>

<?php if ($assets->nextCollection != NULL) { ?>
	<div style="opacity:0;transform:translateY(-650px);" id="load-more" hx-get="/render/asset-list.php?<?= $assets->nextCollection->toHttpGet() ?>" hx-trigger="intersect once" hx-swap="outerHTML"></div>
<?php } else { ?>
	<div id="end-reached" class="results-end-text">
		End of results.
	</div>
<?php } ?>