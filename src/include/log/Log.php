<?php

namespace log;

use Exception;
use log\LogLevel;
use misc\StringUtil;
use Throwable;

class Log
{

	private static LogLevel $level = LogLevel::INFO;
	private static bool $enabled = false;
	private static bool $writeToStdout = false;

	private static string $logName;

	public static function start(string $logName, LogLevel $level = LogLevel::INFO, bool $writeToStdout = false)
	{
		self::$logName = preg_replace('#[^a-zA-Z0-9/-]#', '', $logName);
		self::$level = $level;
		self::$enabled = true;
		self::$writeToStdout = $writeToStdout;

		set_exception_handler([self::class, 'exceptionHandler']);

		self::cleanUpLogDirectory(14); // Delete logs older than 14 days
	}

	public static function exceptionHandler(Throwable $e)
	{
		Log::write("Uncaught exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), LogLevel::EXCEPTION);
		throw $e;
	}

	public static function write(string $message, LogLevel $level = LogLevel::INFO)
	{
		// Return early if loging is disabled or level is too low
		if (!self::$enabled || $level->value < self::$level->value) {
			return;
		}

		$logFilePath = self::getLogFilePath();

		$functionTrace = array_map(
			function ($trace) {
				return  $trace['function'] ?? '';
			},
			array_slice(debug_backtrace(), 1)
		);
		$functionTrace = array_reverse($functionTrace);

		$output = "> " . date('Y-m-d|H:i:s', time());
		$output .=  "\t" . $level->displayName();
		$output .=  "\t" . implode("->", $functionTrace);
		$output .=  "\t"  . $message;
		$output .= "\n";

		Log::createFileIfNotPresent($logFilePath);
		error_log(StringUtil::removeNewline($output), 3, $logFilePath);
		if (self::$writeToStdout) {
			echo StringUtil::removeNewline($output) . PHP_EOL;
		}
	}

	/**
	 * Create the log file based on the log name. 
	 * Create sub-directories if the name contains slashes.
	 * @return string 
	 */
	private static function getLogFilePath(): string
	{
		if (!isset(self::$logName)) {
			throw new Exception("No log name defined.");
		}
		return $_ENV['3D1_LOG_DIRECTORY'] . "/" . date('Y-m-d', time()) . "/" . self::$logName . ".log";
	}

	private static function createFileIfNotPresent($file)
	{
		$logDir = dirname($file) . "/";
		if (!is_dir($logDir)) {
			mkdir($logDir, 0744, true);
		}
		if (!is_file($file)) {
			file_put_contents($file, "");
		}
	}

	/**
	 * Deletes all log files older than a certain number of days from the log directory.
	 * This happens based on the directory names (YYYY-MM-DD).
	 * @param int $deleteOlderThanDays 
	 * @return void 
	 */
	private static function cleanUpLogDirectory($deleteOlderThanDays = 14)
	{
		$logDirectory = $_ENV['3D1_LOG_DIRECTORY'];

		if (!is_dir($logDirectory)) {
			return;
		}

		$directories = array_diff(scandir($logDirectory, SCANDIR_SORT_DESCENDING), ['.', '..']);
		$directoriesToDelete = array_slice($directories, $deleteOlderThanDays);

		foreach ($directoriesToDelete as $directory) {
			$path = $logDirectory . "/" . $directory;
			if (is_dir($path)) {
				array_map('unlink', glob($path . "/*"));
				rmdir($path);
			}
		}
	}
}
