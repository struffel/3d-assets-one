<?php

namespace misc;

use Throwable;

class Log
{

	private static int $logIndent = 0;

	private static string $logName;

	private static string $logDirectory = "";

	public static function write(string $message, string $logType = "")
	{
		if (isset(Log::$logName)) {
			$logFile = Log::$logDirectory . Log::$logName . ".log";
			$message = StringUtil::removeNewline(">" . date('Y-m-d|H:i:s', time()) . "\t" . str_pad($logType, 10) . "\t" . str_repeat("\t", Log::$logIndent) . $message) . "\n";
			Log::createFileIfNotPresent($logFile);
			error_log($message, 3, $logFile);
		}
	}

	public static function stepIn($message = "")
	{
		Log::write("---> $message");
		Log::$logIndent = max(0, Log::$logIndent + 1);
	}

	public static function stepOut($message = "")
	{
		Log::$logIndent = max(0, Log::$logIndent - 1);
		Log::write("<--- $message");
	}

	public static function initialize(string $logName)
	{

		Log::$logDirectory = $_SERVER['DOCUMENT_ROOT'] . "/../log/";

		// Create log dir if it is missing
		if (!file_exists(Log::$logDirectory)) {
			mkdir(Log::$logDirectory, 0777, true);
		}

		Log::cleanUpLogDirectory();
		Log::$logName = $logName . "_" . time();
		Log::write("Initialized logging", "INIT");
	}

	private static function createFileIfNotPresent($file)
	{
		if (!is_file($file)) {
			file_put_contents($file, "");
		}
	}

	public static function echoCurrentLog()
	{
		if (isset(Log::$logName)) {
			try {
				header("content-type: text/plain");
			} catch (Throwable $e) {
				echo "<pre>";
			}

			echo file_get_contents(Log::$logDirectory . Log::$logName . ".log");
		}
	}

	private static function cleanUpLogDirectory($deleteOlderThanDays = 14)
	{
		// Define the time limit (14 days = 14 * 24 * 60 * 60 seconds)
		$timeLimit = time() - ($deleteOlderThanDays * 24 * 60 * 60);

		// Open the directory
		if ($handle = opendir(Log::$logDirectory)) {
			// Loop through the directory
			while (false !== ($file = readdir($handle))) {
				// Exclude current and parent directory entries
				if ($file != "." && $file != "..") {
					$filePath = Log::$logDirectory . $file;

					// Check if the file is a regular file and older than the time limit
					if (is_file($filePath) && filemtime($filePath) < $timeLimit) {
						// Delete the file
						unlink($filePath);
						Log::write("Deleted old log: " . $filePath);
					}
				}
			}
			closedir($handle);
		}
	}
}
