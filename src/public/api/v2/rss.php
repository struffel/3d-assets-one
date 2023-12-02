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
<rss version="2.0">
	<channel>
		<title>3Dassets.one Auto-Generated Asset Feed</title>
		<link>https://3Dassets.one</link>
		<description>A customizable RSS feed containing all newly released 3D models, materials, HDRIs and other resources from creators tracked by 3Dassets.one</description>
		
		<?php foreach($assetCollection->assets as $a){?><item>
			<title><?=$a->name?></title>
			<description>Kurze Zusammenfassung des Eintrags</description>
			<link>https://3dassets.one/go?id=<?=$a->id?></link>
			<author><?=$a->creator->name()?></author>
			<guid><?=$a->id?></guid>
			<pubDate><?=$a->date?></pubDate>
		</item>
<?php } ?>

	</channel>
</rss>