<?php

    function createLog($message,$logType = ""){
        if(isset($GLOBALS['LOGNAME'])){
            $logFile = $_SERVER['DOCUMENT_ROOT']."/log/".$GLOBALS['LOGNAME'].".log";
        	$message = removeNewline(">".date('Y-m-d|H:i:s',time())."\t".str_pad($logType,10)."\t".str_repeat("\t",$GLOBALS['LOGINDENT']).$message)."\n";
        	createFileIfNotPresent($logFile);
        	error_log($message,3,$logFile);
        }
    }

	function changeLogIndentation(bool $indent,$message = ""){
		if(isset($GLOBALS['LOGINDENT'])){
			if($indent){
				createLog("---> $message");
				$GLOBALS['LOGINDENT']++;
			}else{
				$GLOBALS['LOGINDENT']--;
				createLog("<--- $message");
			}
			$GLOBALS['LOGINDENT'] = max(0,$GLOBALS['LOGINDENT']);
		}
	}

    function initializeLog($logName){
		createFolderIfNotPresent($_SERVER['DOCUMENT_ROOT']."/log");
        if(isset($GLOBALS['LOGNAME'])){
            die("Log already set!");
        }else{
            $GLOBALS['LOGNAME'] = $logName."_".time();

        }
		$GLOBALS['LOGINDENT'] = 0;
        createLog("Initialized logging","INIT");
    }

	function echoCurrentLog(){
		if(isset($GLOBALS['LOGNAME'])){
			echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/log/".$GLOBALS['LOGNAME'].".log");
		}
	}
?>