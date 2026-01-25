<?php

use misc\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
Auth::requireAuth();
?>
<ul>
	<?php
	$files = scandir('./');
	foreach ($files as $file) {
		if ($file[0] !== '.' && !is_dir($file)) {
			// Exclude files starting with a dot and display only files (exclude directories)
			echo "<li><a href='$file'>$file</a></li>";
		}
	}
	?>
</ul>