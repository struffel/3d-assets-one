<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
header("content-Type: application/rss+xml");

$query = AssetQuery::fromHttpGet();
$query->sort = SORTING::LATEST;
$query->includeTags = true;
$query->includeQuirks = true;
$assetCollection = AssetLogic::getAssets($query);

echo '<?xml version="1.0" encoding="UTF-8" ?>'.PHP_EOL;

?>
<rss xmlns:media="http://search.yahoo.com/mrss/" version="2.0">
	<channel>
		<title>3Dassets.one Auto-Generated Asset Feed</title>
		<link>https://3Dassets.one</link>
		<description>
			RSS feed containing all newly released 3D models, materials, HDRIs and other resources from creators tracked by 3Dassets.one.
			The selection of assets shown on this feed can be customized using the same search parameters as the main site.
		</description>
		
<?php foreach($assetCollection->assets as $a){?>
		<item>
			<title><?=$a->name?></title>
			<media:thumbnail url="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/<?=$a->id?>.jpg" height="256" width="256"/>
			<description><?=$a->name?> by <?=$a->creator->name()?> / Type: <?=$a->type->name()?> / License: <?=$a->license->name()?> / Tags: <?=implode(",",$a->tags)?></description>
			<link>https://3dassets.one/go?id=<?=$a->id?></link>
			<guid isPermaLink="false" ><?=$a->id?></guid>
			<pubDate><?=(new DateTime($a->date))->format(DateTime::RFC822)?></pubDate>
		</item>
<?php } ?>

	</channel>
</rss>