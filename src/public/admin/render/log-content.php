<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use misc\Auth;

Auth::requireAuth();
header('Cache-Control: no-store');

$logDirectory = $_ENV['3D1_LOG_DIRECTORY'];
$selectedFile = $_GET['file'] ?? '';

// Sanitize path - remove directory traversal attempts
$selectedFile = str_replace(['..', "\0"], '', $selectedFile);

if (empty($selectedFile)) {
	echo '<p class="log-placeholder">Select a log file to view its contents.</p>';
	exit;
}

$fullPath = $logDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $selectedFile);

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

// Read and display file contents
$content = file_get_contents($realFilePath);
if ($content === false) {
	echo '<p class="log-error">Unable to read file.</p>';
	exit;
}
?>
<pre class="log-content"><?= htmlspecialchars($content) ?></pre>