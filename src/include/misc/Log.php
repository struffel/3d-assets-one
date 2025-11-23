<?php

namespace misc;

class Log{

	private static int $logIndent = 0;

	private static string $logName;

	private static string $logDirectory = "";

	public static function write(string $message,string $logType = ""){
		if(isset(LogLogic::$logName)){
			$logFile = LogLogic::$logDirectory.LogLogic::$logName.".log";
			$message = StringLogic::removeNewline(">".date('Y-m-d|H:i:s',time())."\t".str_pad($logType,10)."\t".str_repeat("\t",LogLogic::$logIndent).$message)."\n";
			LogLogic::createFileIfNotPresent($logFile);
			error_log($message,3,$logFile);
		}
	}

	public static function stepIn($message = ""){
		LogLogic::write("---> $message");
		LogLogic::$logIndent = max(0,LogLogic::$logIndent + 1);
	}

	public static function stepOut($message = ""){
		LogLogic::$logIndent = max(0,LogLogic::$logIndent - 1);
		LogLogic::write("<--- $message");
		
	}

	public static function initialize(string $logName){

		LogLogic::$logDirectory = $_SERVER['DOCUMENT_ROOT']."/../log/";
		
		// Create log dir if it is missing
		if (!file_exists(LogLogic::$logDirectory)) {
			mkdir(LogLogic::$logDirectory, 0777, true);
		}
		
		LogLogic::cleanUpLogDirectory();
		LogLogic::$logName = $logName."_".time();
		LogLogic::write("Initialized logging","INIT");
	}

	private static function createFileIfNotPresent($file){
		if(!is_file($file)){
			file_put_contents($file, "");
		}
	}

	public static function echoCurrentLog(){
		if(isset(LogLogic::$logName)){
			try{
				header("content-type: text/plain");
			}catch(Throwable $e){
				echo "<pre>";
			}
			
			echo file_get_contents(LogLogic::$logDirectory.LogLogic::$logName.".log");
		}
	}

	private static function cleanUpLogDirectory($deleteOlderThanDays = 14) {
		// Define the time limit (14 days = 14 * 24 * 60 * 60 seconds)
		$timeLimit = time() - ($deleteOlderThanDays * 24 * 60 * 60);
	
		// Open the directory
		if ($handle = opendir(LogLogic::$logDirectory)) {
			// Loop through the directory
			while (false !== ($file = readdir($handle))) {
				// Exclude current and parent directory entries
				if ($file != "." && $file != "..") {
					$filePath = LogLogic::$logDirectory . $file;
	
					// Check if the file is a regular file and older than the time limit
					if (is_file($filePath) && filemtime($filePath) < $timeLimit) {
						// Delete the file
						unlink($filePath);
						LogLogic::write("Deleted old log: ".$filePath);
					}
				}
			}
			closedir($handle);
		}
	}
	
}