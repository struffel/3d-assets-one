<?php
use obregonco\B2\Client;
use obregonco\B2\Bucket;

class BackblazeB2Logic {

	private static bool $initialized = false;

	private static Client $client;

	private static string $bucketName;

	private static int $version;

	public static function initialize(){
		LogLogic::stepIn(__FUNCTION__);
		if(!BackblazeB2Logic::$initialized){
			LogLogic::write("Initializing connection to Backblaze B2");

			BackblazeB2Logic::$client = new Client(getenv("3D1_B2_ACCOUNTID"), [
				'keyId' => getenv("3D1_B2_KEYID"), // optional if you want to use master key (account Id)
				'applicationKey' => getenv("3D1_B2_APPKEY"),
			]);
			BackblazeB2Logic::$bucketName = getenv("3D1_B2_BUCKETNAME");
			BackblazeB2Logic::$version = 2;
		}else{
			LogLogic::write("Already initialized.");
		}
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function uploadData(string $fileData,string $remotePath) : void {
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Uploading data to '$remotePath'");
		// Upload a file to a bucket. Returns a File object.
		
		$successfulUpload = false;
		while(!$successfulUpload){
			try {
				BackblazeB2Logic::$client->upload([
					'BucketName' => $GLOBALS['B2BUCKET'],
					'FileName' => $remotePath,
					'Body' => $fileData
			
					// The file content can also be provided via a resource.
					// 'Body' => fopen('/path/to/input', 'r')
				]);
				$successfulUpload = true;
				LogLogic::write("Upload OK");
			} catch (\Throwable $th) {
				LogLogic::write("Upload FAILED: ".$th->getMessage(),"B2-ERROR");
				$successfulUpload = false;
				sleep(1);
				LogLogic::write("Trying upload again...");
			}
		}
		
		//var_dump($file);
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function uploadFile(string $localPath,string $remotePath) : void{
		BackblazeB2Logic::uploadData(fopen($localPath, 'r'),$remotePath);
	}

	public static function testForFile(string $remotePath) : bool{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Testing for file '$remotePath'");
		// Retrieve an array of file objects from a bucket.
		$fileList = BackblazeB2Logic::$client->listFiles([
			'BucketName' => $GLOBALS['B2BUCKET'],
			'FileName'=>$remotePath
		]);
		LogLogic::write("Result: ".isset($fileList[0]));
		LogLogic::stepOut(__FUNCTION__);
		return isset($fileList[0]);
	}
}

// Initalization call
BackblazeB2Logic::initialize();

?>