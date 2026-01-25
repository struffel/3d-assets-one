<?php

namespace log;

use Exception;
use FilesystemIterator;
use log\LogLevel;
use misc\StringUtil;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class Log
{

	private static LogLevel $level = LogLevel::INFO;
	private static bool $enabled = false;
	private static bool $writeToStdout = false;
	private static string $logName;

	private static bool $finalized = false;

	public static function stop(LogResult $result)
	{
		if (self::$finalized) {
			throw new Exception("Logger has already been stopped.");
		}
		if (!self::$enabled) {
			throw new Exception("Logger is not enabled.");
		}

		Log::write("Stopped logging");

		// Move log file to dated sub-directory
		$logFilePath = self::getLogFilePath();
		$newLogFilePath = self::getLogFilePath($result->value);
		rename($logFilePath, $newLogFilePath);

		self::$finalized = true;
		self::$enabled = false;

		self::cleanUpLogDirectory(7);
	}


	public static function start(string $logName, LogLevel $level = LogLevel::INFO, bool $writeToStdout = false)
	{

		if (self::$enabled) {
			throw new Exception("Logging has already been started.");
		}

		self::$logName = preg_replace('#[^a-zA-Z0-9/-]#', '', $logName);
		self::$level = $level;
		self::$enabled = true;
		self::$writeToStdout = $writeToStdout;

		set_exception_handler([self::class, 'exceptionHandler']);

		self::cleanUpLogDirectory(30); // Delete logs older than 30 days

		Log::write("Started logging", ["Name" => $logName, "Level" => $level]);
	}

	public static function exceptionHandler(Throwable $th)
	{
		$exceptionDetails = [
			'message' => $th->getMessage(),
			'code' => $th->getCode(),
			'file' => $th->getFile(),
			'line' => $th->getLine(),
			'trace' => explode("\n", $th->getTraceAsString()),
		];

		Log::write("Uncaught exception", $exceptionDetails, LogLevel::EXCEPTION);
		Self::stop(LogResult::ERR);
		throw $th;
	}

	public static function write(string $message, mixed $data = null, LogLevel $level = LogLevel::INFO)
	{
		// Return early if loging is disabled or level is too low
		if (!self::$enabled || $level->value < self::$level->value) {
			return;
		}

		$functionTrace = array_map(
			function ($trace) {
				return  $trace['function'] ?? '';
			},
			array_slice(debug_backtrace(), 1)
		);
		$functionTrace = array_reverse($functionTrace);

		$output = ">" . date('Y-m-d H:i:s', time());
		$output .=  " " . $level->displayName();
		$output .=  " ->" . implode("->", $functionTrace);

		$outputPrefixLength = strlen($output);

		$output .=  " | "  . $message;
		$output .= PHP_EOL;

		if ($data) {
			$dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
			/*foreach (explode(PHP_EOL, $dataJson) as $line) {
				$output .= str_repeat(" ", $outputPrefixLength) . " " . $line . PHP_EOL;
			}*/
			$output .= $dataJson . PHP_EOL;
		}

		self::writeRaw($output);
	}

	private static function writeRaw(string $rawMessage)
	{
		$logFilePath = self::getLogFilePath();
		Log::createFileIfNotPresent($logFilePath);
		error_log($rawMessage, 3, $logFilePath);
		if (self::$writeToStdout) {
			echo $rawMessage;
		}
	}

	/**
	 * Create the log file based on the log name. 
	 * Create sub-directories if the name contains slashes.
	 * @return string 
	 */
	private static function getLogFilePath(string $suffix = "run"): string
	{
		if (!isset(self::$logName)) {
			throw new Exception("No log name defined.");
		}
		return $_ENV['3D1_LOG_DIRECTORY'] . "/" . self::$logName . ($suffix ? ".$suffix" : "") . ".log";
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
	 * Recrusively delete all .log files older than specified days.
	 * @param int $deleteOlderThanDays 
	 * @return void 
	 */
	private static function cleanUpLogDirectory($deleteOlderThanDays = 14)
	{
		$logDirectory = $_ENV['3D1_LOG_DIRECTORY'];

		if (!is_dir($logDirectory)) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($logDirectory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		$now = time();
		foreach ($files as $fileinfo) {
			if ($fileinfo->isFile() && $fileinfo->getExtension() === 'log') {
				$fileAgeInDays = ($now - $fileinfo->getCTime()) / (60 * 60 * 24);
				if ($fileAgeInDays > $deleteOlderThanDays) {
					unlink($fileinfo->getRealPath());
				}
			}
		}
	}
}
