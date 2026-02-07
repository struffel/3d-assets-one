<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use log\Log;
use log\LogLevel;
use misc\Auth;

Auth::requireAuth();
header('Cache-Control: no-store');

$logDirectory = Log::getLogDirectory();
$selectedFile = $_GET['file'] ?? '';
$minLevel = (int)($_GET['level'] ?? 2); // Default to INFO level

// Sanitize path - remove directory traversal attempts
$selectedFile = str_replace(['..', "\0"], '', $selectedFile);

if (empty($selectedFile)) {
	echo '<p class="log-placeholder">Select a log file to view its contents.</p>';
	exit;
}

$fullPath = $logDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$selectedFile);

// Validate file exists and is within log directory
$realLogDir = realpath($logDirectory);
$realFilePath = realpath($fullPath);

if ($realFilePath === false || $realLogDir === false || strpos($realFilePath, $realLogDir) !== 0) {
	echo '<p class="log-error">Invalid file path.</p>';
	exit;
}

if (!is_file($realFilePath)) {
	echo '<p class="log-error">File not found.</p>';
	exit;
}

// Read file contents
$content = file_get_contents($realFilePath);
if ($content === false) {
	echo '<p class="log-error">Unable to read file.</p>';
	exit;
}

// Parse and filter log entries by level
$lines = explode("\n", $content);
$filteredLines = [];

foreach ($lines as $line) {
	$line = trim($line);
	if (empty($line)) {
		continue;
	}

	$entry = json_decode($line, true);
	if ($entry === null) {
		// Non-JSON line, include it if showing DEBUG level
		if ($minLevel <= LogLevel::DEBUG->value) {
			$filteredLines[] = $line;
		}
		continue;
	}

	$entryLevel = LogLevel::fromName($entry['level'] ?? null);
	if ($entryLevel === null) {
		// Unknown level, include if showing DEBUG
		if ($minLevel <= LogLevel::DEBUG->value) {
			$filteredLines[] = $line;
		}
		continue;
	}

	// Include entry if its level meets minimum threshold
	if ($entryLevel->value >= $minLevel) {
		$filteredLines[] = $line;
	}
}

?>
<pre class="log-content">
<?php echo highlight_string(implode("\n", $filteredLines), true); ?>
</pre>