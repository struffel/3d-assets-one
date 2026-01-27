<?php

use asset\StoredAssetQuery;
use asset\AssetSorting;
use asset\StoredAsset;
use thumbnail\ThumbnailFormat;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-Type: application/rss+xml");

$query = StoredAssetQuery::fromHttpGet();
$query->sort = AssetSorting::LATEST;
$assetCollection = $query->execute();

echo '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;

?>
<rss xmlns:media="http://search.yahoo.com/mrss/" version="2.0">
	<channel>
		<title>3Dassets.one Auto-Generated Asset Feed</title>
		<link>https://3Dassets.one</link>
		<description>
			RSS feed containing all newly released 3D models, materials, HDRIs and other resources from creators tracked by 3Dassets.one.
			The selection of assets shown on this feed can be customized using the same search parameters as the main site.
		</description>

		<?php
		/** @var StoredAsset $a */
		foreach ($assetCollection as $a) { ?>
			<item>
				<title><?= htmlspecialchars($a->title) ?></title>
				<media:thumbnail url="<?= $a->getThumbnailUrl(ThumbnailFormat::JPG_256_FFFFFF, true) ?>" height="256" width="256" />
				<description><?= htmlspecialchars($a->title) ?> by <?= $a->creator->name ?> / Type: <?= $a->type->name() ?> / License: <?= $a->creator->commonLicense()->name() ?> / Tags: <?= implode(",", $a->tags) ?></description>
				<link>https://<?= $_SERVER['HTTP_HOST'] ?>/go?id=<?= $a->id ?></link>
				<guid isPermaLink="false">3D1-<?= $a->id ?></guid>
				<pubDate><?= $a->date->format(DateTime::RFC822) ?></pubDate>
			</item>
		<?php } ?>

	</channel>
</rss>