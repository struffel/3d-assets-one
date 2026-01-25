<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use misc\Auth;

Auth::requireAuth();

$logDirectory = $_ENV['3D1_LOG_DIRECTORY'];
$selectedDir = $_GET['directory'] ?? '';
$selectedFile = $_GET['file'] ?? '';

// Sanitize paths
$selectedDir = str_replace(['..', "\0"], '', $selectedDir);
$selectedFile = str_replace(['..', "\0", '/', '\\'], '', $selectedFile);

if (empty($selectedFile)) {
	echo '<p class="log-placeholder">Select a log file to view its contents.</p>';
	exit;
}

$fullPath = $logDirectory . DIRECTORY_SEPARATOR . $selectedDir . DIRECTORY_SEPARATOR . $selectedFile;

// Validate file exists and is within log directory
$realLogDir = realpath($logDirectory);
$realFilePath = realpath($fullPath);

if ($realFilePath === false || strpos($realFilePath, $realLogDir) !== 0) {
	echo '<p class="log-error">Invalid file path.</p>';
	exit;
}

if (!is_file($realFilePath)) {
	echo '<p class="log-error">File not found.</p>';
	exit;
}

// Read and display file contents
$content = file_get_contents($realFilePath);
if ($content === false) {
	echo '<p class="log-error">Unable to read file.</p>';
	exit;
}
?>

<div class="log-file-header">
	<strong><?= htmlspecialchars($selectedDir . '/' . $selectedFile) ?></strong>
</div>
<pre class="log-content"><?= htmlspecialchars($content) ?></pre>