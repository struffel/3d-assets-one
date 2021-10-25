<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';
use obregonco\B2\Client;
use obregonco\B2\Bucket;

function initializeBackblazeB2(){
	changeLogIndentation(true,__FUNCTION__);
	createLog("Initializing connection to Backblaze B2");
	if(!isset($GLOBALS['B2'])){
		$loginData = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/../_logins/backblazeB2Images.ini');
		$GLOBALS['B2'] = new Client($loginData['accountId'], [
			'keyId' => $loginData['keyId'], // optional if you want to use master key (account Id)
			'applicationKey' => $loginData['applicationKey'],
		]);
		$GLOBALS['B2']->version = 2; // By default will use version 1
		$GLOBALS['B2BUCKET'] = $loginData['bucketName'];
	}
	changeLogIndentation(false,__FUNCTION__);
}

function uploadDataToBackblazeB2($fileData,$remotePath){
	changeLogIndentation(true,__FUNCTION__);
	createLog("Uploading data to '$remotePath'");
	// Upload a file to a bucket. Returns a File object.
	
	if(!isset($GLOBALS['B2'])){
		initializeBackblazeB2();
	}
	$successfulUpload = false;
	while(!$successfulUpload){
		try {
			$file = $GLOBALS['B2']->upload([
				'BucketName' => $GLOBALS['B2BUCKET'],
				'FileName' => $remotePath,
				'Body' => $fileData
		
				// The file content can also be provided via a resource.
				// 'Body' => fopen('/path/to/input', 'r')
			]);
			$successfulUpload = true;
			createLog("Upload OK");
		} catch (\Throwable $th) {
			createLog("Upload FAILED: ".$th->getMessage(),"B2-ERROR");
			$successfulUpload = false;
			sleep(1);
			createLog("Trying upload again...");
		}
	}
	
	//var_dump($file);
	changeLogIndentation(false,__FUNCTION__);
}

function uploadFileToBackblazeB2($localPath,$remotePath){
	uploadDataToBackblazeB2(fopen($localPath, 'r'),$remotePath);
}

function testForFileOnBackblazeB2($fileName){
	changeLogIndentation(true,__FUNCTION__);
	createLog("Testing for file '$fileName'");
	// Retrieve an array of file objects from a bucket.
	$fileList = $GLOBALS['B2']->listFiles([
		'BucketName' => $GLOBALS['B2BUCKET'],
		'FileName'=>$fileName
	]);
	createLog("Result: ".isset($fileList[0]));
	changeLogIndentation(false,__FUNCTION__);
	return isset($fileList[0]);
}

?>