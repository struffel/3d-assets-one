<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

foreach (glob($_SERVER['DOCUMENT_ROOT'] . '/../creators/*.php') as $file) {
	require_once $file;
}
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
<?php
phpinfo();
?>