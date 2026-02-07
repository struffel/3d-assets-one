<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use blocks\AdminHeaderBlock;
use blocks\HeadBlock;
use blocks\LogoBlock;
use log\Log;
use log\LogLevel;
use misc\Auth;

Auth::requireAuth();

$logDirectory = Log::getLogDirectory();

// Get all log files recursively, grouped by directory
$filesByDirectory = [];

if (is_dir($logDirectory)) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($logDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	$baseDirLength = strlen($logDirectory) + 1; // +1 for the separator

	foreach ($iterator as $file) {

		if ($file->isFile()) {
			// Get relative path from base directory
			$fullPath = $file->getPathname();
			$relativePath = substr($fullPath, $baseDirLength);
			// Normalize to forward slashes
			$relativePath = str_replace('\\', '/', $relativePath);

			// Split into directory and filename
			$lastSlash = strrpos($relativePath, '/');
			if ($lastSlash !== false) {
				$dir = substr($relativePath, 0, $lastSlash);
				$filename = substr($relativePath, $lastSlash + 1);
			} else {
				$dir = '';
				$filename = $relativePath;
			}

			if (!isset($filesByDirectory[$dir])) {
				$filesByDirectory[$dir] = [];
			}

			$newFile = [
				'name' => $filename,
				'path' => $relativePath,
				'size' => $file->getSize(),
			];

			$successful = Log::logIsSuccessful($fullPath);

			if ($successful !== null) {
				$newFile['successful'] = $successful ? 1 : 0;
			} else {
				$newFile['successful'] = "";
			}

			$filesByDirectory[$dir][] = $newFile;
		}
	}
} else {
	echo '<p class="log-error">Log directory not found.</p>';
	exit;
}

// Sort directories
ksort($filesByDirectory);

// Sort files within each directory (newest first by name)
foreach ($filesByDirectory as &$files) {
	usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));
}
unset($files);

?>

<!DOCTYPE html>
<html lang="en">
<?php HeadBlock::render(); ?>
<link rel="stylesheet" href="/css/page/logs.css">

<body>
	<?php AdminHeaderBlock::render(); ?>
	<div id="logs-container">
		<!-- Left sidebar -->
		<aside id="logs-sidebar">

			<!-- Log level filter -->
			<label for="level-select">Minimum Log Level:</label>
			<select
				id="level-select"
				name="level"
				hx-trigger="change"
				hx-get="/admin/render/log-content.php"
				hx-target="main"
				hx-include="#file-list"
				hx-swap="innerHTML">
				<option value="<?= LogLevel::DEBUG->value ?>" selected><?= LogLevel::DEBUG->name ?> (Everything)</option>
				<option value="<?= LogLevel::INFO->value ?>"><?= LogLevel::INFO->name ?></option>
				<option value="<?= LogLevel::WARNING->value ?>"><?= LogLevel::WARNING->name ?></option>
				<option value="<?= LogLevel::ERROR->value ?>"><?= LogLevel::ERROR->name ?></option>
			</select>

			<!-- Log file selector -->
			<label for="file-list">Log File:</label>
			<select
				id="file-list"
				name="file"
				size="20"
				hx-get="/admin/render/log-content.php"
				hx-target="main"
				hx-trigger="change"
				hx-include="#level-select"
				hx-swap="innerHTML">
				<?php foreach ($filesByDirectory as $dir => $files) { ?>
					<optgroup label="<?= htmlspecialchars($dir ?: '(root)') ?>">
						<?php foreach ($files as $file) { ?>
							<option
								value="<?= htmlspecialchars($file['path']) ?>"
								data-successful="<?= $file['successful']  ?>"
								data-filesize="<?= round($file['size'] / 1024, 2) ?> KiB">
								<?= htmlspecialchars($file['name']) ?>
							</option>
						<?php } ?>
					</optgroup>
				<?php } ?>
			</select>
		</aside>

		<!-- Main content area -->
		<main>
		</main>
	</div>
</body>

</html>