<?php

namespace blocks;

class FooterBlock
{
	public static function render()
	{ ?>

		<link rel="stylesheet" href="/css/component/footer.css">
		<footer>
			by <a class="subtle-link" href="https://ambientCG.com">ambientCG.com</a>
			&nbsp;•&nbsp;
			<a class="subtle-link" href="https://patreon.com/ambientCG">Patreon</a>
			&nbsp;•&nbsp;
			<a class="subtle-link" href="https://docs.ambientCG.com/legal">Imprint</a>
		</footer>
<?php
	}
}
