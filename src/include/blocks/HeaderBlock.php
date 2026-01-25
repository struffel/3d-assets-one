<?php

namespace blocks;

use database\Database;

class HeaderBlock
{
	public static function render()
	{

?>

		<link rel="stylesheet" href="/css/component/header.css">
		<header>
			<div class="logo">
				<?php LogoBlock::render(); ?>
			</div>
			<div class="logo-slogan">
				The 3D Asset Search Engine
			</div>
			<nav class="navbar">
				<a class="prominent-link" href="/">Assets</a>
				<a class="prominent-link" href="/about-creators">Creators</a>
				<a class="prominent-link" href="/about-site">About</a>
			</nav>
		</header>
<?php
	}
}
