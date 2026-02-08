<?php

namespace blocks;

use asset\StoredAssetQuery;
use database\Database;

class FooterBlock
{
	public static function render(): void
	{

?>

		<link rel="stylesheet" href="/css/component/footer.css">
		<footer>
			<p>
				<a class="subtle-link" href="https://patreon.com/ambientCG">Patreon</a>
				&nbsp;•&nbsp;
				<a class="subtle-link" href="https://github.com/struffel/3d-assets-one">GitHub</a>
				&nbsp;•&nbsp;
				<a class="subtle-link" href="https://docs.ambientCG.com/legal">Imprint</a>
			</p>
		</footer>
<?php
	}
}
