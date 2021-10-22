<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/tools/files.php';

    function createLog($message,$logType){
        if(isset($GLOBALS['LOGNAME'])){
            $logFile = $_SERVER['DOCUMENT_ROOT']."/log/".$GLOBALS['LOGNAME'].".log";
        $message = date('Y-m-d|H:i:s',time())."\t".str_pad($logType,10)."\t".$message."\n";
        createFileIfNotPresent($logFile);
        error_log($message,3,$logFile);
        }
    }

    function initializeLog($logName){
        if(isset($GLOBALS['LOGNAME'])){
            die("Log already set!");
        }else{
            $GLOBALS['LOGNAME'] = $logName;

        }
        createLog("--- Initialized New Logging","INIT");
    }
?>