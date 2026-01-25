<?php

namespace blocks;

use database\Database;

class FooterBlock
{
	public static function render()
	{
		$assetCount = Database::runQuery("SELECT COUNT(*) AS count FROM Asset", [])->fetchArray()['count'];

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
