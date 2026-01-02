<?php

namespace misc;

use log\LogLevel;
use log\Log;
use obregonco\B2\Client;
use obregonco\B2\Bucket;



class BackblazeB2
{

	private static bool $initialized = false;

	private static Client $client;

	private static string $bucketName;

	private static int $version;

	public static function initialize()
	{

		if (!BackblazeB2::$initialized) {
			Log::write("Initializing connection to Backblaze B2");

			BackblazeB2::$client = new Client($_ENV["3D1_B2_ACCOUNTID"], [
				'keyId' => $_ENV["3D1_B2_KEYID"], // optional if you want to use master key (account Id)
				'applicationKey' => $_ENV["3D1_B2_APPKEY"],
			]);
			BackblazeB2::$bucketName = $_ENV["3D1_B2_BUCKETNAME"];
			BackblazeB2::$version = 2;
		} else {
			Log::write("Already initialized.");
		}
	}

	public static function uploadData(string $fileData, string $remotePath): void
	{

		Log::write("Uploading data to '$remotePath'");
		BackblazeB2::initialize();
		// Upload a file to a bucket. Returns a File object.

		$successfulUpload = false;
		while (!$successfulUpload) {
			try {
				BackblazeB2::$client->upload([
					'BucketName' => BackblazeB2::$bucketName,
					'FileName' => $remotePath,
					'Body' => $fileData

					// The file content can also be provided via a resource.
					// 'Body' => fopen('/path/to/input', 'r')
				]);
				$successfulUpload = true;
				Log::write("Upload OK");
			} catch (\Throwable $th) {
				Log::write("Upload FAILED: " . $th->getMessage(), LogLevel::ERROR);
				$successfulUpload = false;
				sleep(1);
				Log::write("Trying upload again...");
			}
		}

		//var_dump($file);

	}

	public static function uploadFile(string $localPath, string $remotePath): void
	{
		BackblazeB2::uploadData(fopen($localPath, 'r'), $remotePath);
	}

	public static function testForFile(string $remotePath): bool
	{

		Log::write("Testing for file '$remotePath'");
		BackblazeB2::initialize();
		// Retrieve an array of file objects from a bucket.
		$fileList = BackblazeB2::$client->listFiles([
			'BucketName' => BackblazeB2::$bucketName,
			'FileName' => $remotePath
		]);
		Log::write("Result: " . isset($fileList[0]));

		return isset($fileList[0]);
	}
}
