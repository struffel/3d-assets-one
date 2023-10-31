<?php

class LogLogic{

	private static int $logIndent = 0;

	private static string $logName;

	public static function write(string $message,string $logType = ""){
		if(isset(LogLogic::$logName)){
			$logFile = $_SERVER['DOCUMENT_ROOT']."/../log/".LogLogic::$logName.".log";
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
		LogLogic::createFolderIfNotPresent($_SERVER['DOCUMENT_ROOT']."/../log");
		LogLogic::$logName = $logName."_".time();
		LogLogic::write("Initialized logging","INIT");
	}

	private static function createFileIfNotPresent($file){
		if(!is_file($file)){
			file_put_contents($file, "");
		}
	}
	
	private static function createFolderIfNotPresent($folder){
		if (!file_exists($folder)) {
			mkdir($folder, 0777, true);
		}
	}

	public static function echoCurrentLog(){
		if(isset(LogLogic::$logName)){
			try{
				header("content-type: text/plain");
			}catch(Throwable $e){
				echo "<pre>";
			}
			
			echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/../log/".LogLogic::$logName.".log");
		}
	}
}