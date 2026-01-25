<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use misc\Auth;

Auth::requireAuth();

header('Cache-Control: no-store');

$logDirectory = $_ENV['3D1_LOG_DIRECTORY'];
$selectedDir = $_GET['directory'] ?? '';

// Sanitize and validate directory path
$selectedDir = str_replace(['..', "\0"], '', $selectedDir);
$fullPath = $logDirectory . DIRECTORY_SEPARATOR . $selectedDir;

// Get log files in the selected directory
$files = [];
if (is_dir($fullPath)) {
	$entries = scandir($fullPath, SCANDIR_SORT_DESCENDING);
	foreach ($entries as $entry) {
		if ($entry !== '.' && $entry !== '..' && is_file($fullPath . DIRECTORY_SEPARATOR . $entry)) {
			// Only include .log files
			if (pathinfo($entry, PATHINFO_EXTENSION) === 'log') {
				$files[] = $entry;
			}
		}
	}
}

// Add HTMX attributes to trigger content load on change
header('HX-Trigger-After-Swap: logFilesLoaded');
?>

<?php if (empty($files)) { ?>
	<option value="">No log files found</option>
<?php } else { ?>
	<?php foreach ($files as $file) { ?>
		<option
			value="<?= htmlspecialchars($file) ?>"
			data-filesize="<?= round(filesize($fullPath . DIRECTORY_SEPARATOR . $file) / 1024, 2) . " KiB" ?>">
			<?= htmlspecialchars($file) ?>
		</option>
	<?php } ?>
<?php } ?>