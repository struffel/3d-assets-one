<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use blocks\HeadBlock;
use blocks\LogoBlock;
use misc\Auth;

Auth::requireAuth();

$logDirectory = $_ENV['3D1_LOG_DIRECTORY'];

// Get all subdirectories recursively as flat list
$directories = [];
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($logDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
	if ($file->isDir()) {
		// Get relative path from base directory
		$relativePath = str_replace($logDirectory . DIRECTORY_SEPARATOR, '', $file->getPathname());
		$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
		if (is_string($relativePath)) {
			$directories[] = $relativePath;
		}
	}
}

sort($directories);


?>

<!DOCTYPE html>
<html lang="en">
<?php HeadBlock::render(); ?>
<link rel="stylesheet" href="/css/page/logs.css">

<body>
	<div id="logs-container">
		<!-- Left sidebar -->
		<aside id="logs-sidebar">
			<div id="logs-header">
				<?php LogoBlock::render(); ?>
				Log Viewer
			</div>

			<!-- Directory selector -->
			<label for="directory-select">Directory:</label>
			<select
				id="directory-select"
				name="directory"
				hx-get="/admin/render/log-files.php"
				hx-target="#file-list"
				hx-trigger="change, load"
				hx-swap="innerHTML">
				<?php foreach ($directories as $dir) { ?>
					<option value="<?= htmlspecialchars($dir) ?>">
						<?= htmlspecialchars($dir) ?>
					</option>
				<?php } ?>
			</select>

			<!-- File listing -->
			<label for="file-select">Log File:</label>
			<select
				id="file-list"
				name="file"
				size="10"
				hx-get="/admin/render/log-content.php"
				hx-target="#log-content"
				hx-trigger="change"
				hx-include="#directory-select"
				hx-swap="innerHTML">
				<option value="">Select a directory first...</option>
			</select>
		</aside>

		<!-- Main content area -->
		<main>
			<div id="log-content">
				<p class="log-placeholder">Select a log file to view its contents.</p>
			</div>
		</main>
	</div>
</body>

</html>