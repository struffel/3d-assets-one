<?php

namespace log;

use DateTime;
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

	private static bool $enabled = false;
	private static bool $writeToStdout = false;
	private static string $logName;
	private static bool $finalized = false;

	public static function timestampHelper(): string
	{
		return (new DateTime())->format('Y-m-d\TH-i-s-v');
	}


	public static function start(string $logName,  bool $writeToStdout = false): void
	{

		if (self::$enabled) {
			throw new Exception("Logging has already been started.");
		}

		self::$logName = preg_replace('#[^a-zA-Z0-9/_-]#', '', $logName) ?? throw new Exception("Invalid log name.");
		self::$enabled = true;
		self::$writeToStdout = $writeToStdout;

		set_exception_handler([self::class, 'exceptionHandler']);

		Log::write("Started logging", ["path" => self::getLogFilePath()], LogLevel::SYSTEM);
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

		Log::write("Uncaught exception", $exceptionDetails, LogLevel::EXCEPTION);
		Log::stop(false);
		throw $th;
	}

	public static function logIsSuccessful(string $logFilePath): ?bool
	{
		if (str_ends_with($logFilePath, '.ok.log')) {
			return true;
		}

		if (str_ends_with($logFilePath, '.err.log')) {
			return false;
		}

		return null;
	}

	public static function write(string $message, mixed $data = null, LogLevel $level = LogLevel::INFO): void
	{

		if (self::$finalized) {
			throw new Exception("Logger has already been stopped.");
		}

		// Return early if loging is disabled or level is too low
		if (!self::$enabled) {
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
	}

	public static function stop(bool $successful = true): void
	{

		if (self::$finalized) {
			throw new Exception("Logger has already been stopped.");
		}

		Log::write("Stopping logging", ["Successful" => $successful], LogLevel::SYSTEM);

		// Move the log file based on success or failure
		$newFilePath = self::getLogFilePath();
		$newFilePath = str_replace(".log", $successful ? ".ok.log" : ".err.log", $newFilePath);

		Log::write("Moving log", $newFilePath, LogLevel::INFO);

		rename(self::getLogFilePath(), $newFilePath);

		self::$finalized = true;
		self::$enabled = false;

		self::cleanUpLogDirectory(3);
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
		return self::getLogDirectory() . "/" . self::$logName . ".log";
	}

	public static function getLogDirectory(): string
	{
		return __DIR__ . "/../../data/log";
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
	private static function cleanUpLogDirectory(int $deleteOlderThanDays): void
	{
		$logDirectory = self::getLogDirectory();

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
