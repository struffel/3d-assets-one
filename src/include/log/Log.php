<?php

namespace log;

use Exception;
use FilesystemIterator;
use LimitIterator;
use log\LogLevel;
use misc\StringUtil;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;
use Throwable;

class Log
{

	private static LogLevel $level = LogLevel::INFO;
	private static bool $enabled = false;
	private static bool $writeToStdout = false;
	private static string $logName;
	private static bool $finalized = false;


	public static function start(string $logName, LogLevel $level = LogLevel::INFO, bool $writeToStdout = false): void
	{

		if (self::$enabled) {
			throw new Exception("Logging has already been started.");
		}

		self::$logName = preg_replace('#[^a-zA-Z0-9/_-]#', '', $logName) ?? throw new Exception("Invalid log name.");
		self::$level = $level;
		self::$enabled = true;
		self::$writeToStdout = $writeToStdout;

		set_exception_handler([self::class, 'exceptionHandler']);

		Log::write("Started logging", ["Name" => $logName, "Level" => $level]);
	}

	public static function exceptionHandler(Throwable $th): never
	{
		$exceptionDetails = [
			'message' => $th->getMessage(),
			'code' => $th->getCode(),
			'file' => $th->getFile(),
			'line' => $th->getLine(),
			'trace' => explode("\n", $th->getTraceAsString()),
		];

		Log::write("Uncaught exception", $exceptionDetails, LogLevel::FINISH_FAILED);
		throw $th;
	}

	public static function logIsSuccessful(string $logFilePath): ?bool
	{

		$file = new SplFileObject($logFilePath, 'r');
		$file->seek(PHP_INT_MAX);
		$totalLines = $file->key();
		if ($totalLines <= 1) {
			return false;
		}

		// Read the last 10 lines to find FINISH_OK or FINISH_FAILED
		$reader = new LimitIterator($file, max(0, $totalLines - 10));

		foreach ($reader as $line) {
			$entry = json_decode($line, true);
			if (isset($entry['level'])) {
				if ($entry['level'] === LogLevel::FINISH_OK->name) {
					return true;
				} elseif ($entry['level'] === LogLevel::FINISH_FAILED->name) {
					return false;
				}
			}
		}



		return null;
	}

	public static function read(string $logFilePath): array
	{
		if (!is_file($logFilePath)) {
			throw new Exception("Log file not found: " . $logFilePath);
		}

		$lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$entries = array_map(
			function ($line) {
				return json_decode($line, true);
			},
			$lines
		);

		$entries = array_filter($entries, function ($entry) {
			return $entry !== null;
		});

		return $entries;
	}

	public static function write(string $message, mixed $data = null, LogLevel $level = LogLevel::INFO): void
	{

		if (self::$finalized) {
			throw new Exception("Logger has already been stopped.");
		}

		// Return early if loging is disabled or level is too low
		if (!self::$enabled || $level->value < self::$level->value) {
			return;
		}

		$functionTrace = array_map(
			function ($trace) {
				return  $trace['function'];
			},
			array_slice(debug_backtrace(), 1)
		);

		$functionTrace = array_reverse($functionTrace);

		// Prepare log entry
		$outputData = [
			'time' => date('Y-m-d H:i:s', time()),
			'level' => $level->name,
			'functions' => $functionTrace,
			'message' => $message,
			'data' => $data,
		];

		// Generate a log row as JSON (without newlines)
		$output = json_encode($outputData,  JSON_UNESCAPED_SLASHES) . PHP_EOL;

		self::writeRaw($output);

		if ($level === LogLevel::FINISH_OK || $level === LogLevel::FINISH_FAILED) {
			self::$finalized = true;
			self::$enabled = false;
			self::cleanUpLogDirectory(14);
		}
	}

	private static function writeRaw(string $rawMessage): void
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
	private static function getLogFilePath(): string
	{
		if (!isset(self::$logName)) {
			throw new Exception("No log name defined.");
		}
		return $_ENV['3D1_LOG_DIRECTORY'] . "/" . self::$logName . ".log";
	}

	private static function createFileIfNotPresent(string $file): void
	{
		$logDir = dirname($file) . "/";
		if (!is_dir($logDir)) {
			mkdir($logDir, 0755, true);
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
	private static function cleanUpLogDirectory($deleteOlderThanDays = 14): void
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
