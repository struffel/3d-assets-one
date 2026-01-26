<?php

namespace blocks;

use asset\StoredAssetQuery;
use database\Database;

class FooterBlock
{
	public static function render()
	{
		$assetCount = StoredAssetQuery::assetCountTotal();

?>

		<link rel="stylesheet" href="/css/component/footer.css">
		<footer>
			<p>
				by <a class="subtle-link" href="https://ambientCG.com">ambientCG.com</a>
				&nbsp;•&nbsp;
				<a class="subtle-link" href="https://patreon.com/ambientCG">Patreon</a>
				&nbsp;•&nbsp;
				<a class="subtle-link" href="https://docs.ambientCG.com/legal">Imprint</a>
			</p>
			<p>
				Total Assets: <?= $assetCount ?>
			</p>
		</footer>
<?php
	}
}
