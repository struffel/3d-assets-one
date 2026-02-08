<?php

namespace blocks;

use database\Database;

class AdminHeaderBlock
{
	public static function render(): void
	{

?>

		<link rel="stylesheet" href="/css/component/admin-header.css">
		<header>
			<span class="logo">
				<?php LogoBlock::render(); ?>
			</span>
			<nav class="navbar">
				<a class="prominent-link" href="/admin/availability">Availability</a>
				<a class="prominent-link" href="/admin/editor">Editor</a>
				<a class="prominent-link" href="/admin/logs">Logs</a>
				<a class="prominent-link" href="/admin/phpinfo">PHP Info</a>
			</nav>
		</header>
<?php
	}
}
