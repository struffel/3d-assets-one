<?php

namespace misc;

use asset\Asset;
use log\LogLevel;
use mysqli;
use mysqli_result;

class Database
{

	private static mysqli $connection;

	public static function initializeConnection()
	{

		Log::write("Initializing DB Connection");


		// Create connection
		if (!isset(self::$connection)) {
			self::$connection = new mysqli($_ENV["3D1_DB_SERVER"], $_ENV["3D1_DB_USERNAME"], $_ENV["3D1_DB_PASSWORD"]);
			Log::write("Initialized DB connection to: " . $_ENV["3D1_DB_SERVER"]);
		}

		// Check connection
		if (self::$connection->connect_error) {
			Log::write("Connection failed: " . self::$connection->connect_error, LogLevel::ERROR);
		}

		$query = "use " . $_ENV["3D1_DB_NAME"] . ";";
		self::$connection->query($query);
		Log::write("Selected DB: " . $_ENV["3D1_DB_NAME"]);
	}

	public static function generatePlaceholder(array $array)
	{
		if (sizeof($array) < 1) {
			return "";
		} else {
			return "?" . str_repeat(",?", sizeof($array) - 1);
		}
	}

	public static function startTransaction()
	{
		if (!isset(self::$connection)) {
			self::initializeConnection();
		}
		Log::write("Start transaction...");
		self::$connection->query("START TRANSACTION;");
		if (self::$connection->error) {
			Log::write("SQL execution ERROR: " . self::$connection->error, LogLevel::ERROR);
		} else {
			Log::write("SQL OK");
		}
	}

	public static function commitTransaction()
	{
		if (!isset(self::$connection)) {
			self::initializeConnection();
		}
		Log::write("Commit transaction...");
		self::$connection->query("COMMIT;");
		if (self::$connection->error) {
			Log::write("SQL execution ERROR: " . self::$connection->error, LogLevel::ERROR);
		} else {
			Log::write("SQL OK");
		}
	}

	public static function runQuery(string $sql, array $parameters = []): mysqli_result|bool
	{

		Log::write("Received SQL query to run: " . $sql . " (" . print_r($parameters, true) . ")");

		if (!isset(self::$connection)) {
			self::initializeConnection();
		}

		if (sizeof($parameters) > 0) {

			// Turn any enums into their native representation and DateTime with a string
			for ($i = 0; $i < sizeof($parameters); $i++) {

				if ($parameters[$i] instanceof \BackedEnum) {
					$parameters[$i] = $parameters[$i]->value;
				}

				if ($parameters[$i] instanceof \DateTime) {
					$parameters[$i] = $parameters[$i]->format('Y-m-d H:i:s');
				}
			}


			$result = self::$connection->execute_query($sql, $parameters);
			if (self::$connection->error) {
				Log::write("SQL execution ERROR: " . self::$connection->error, LogLevel::ERROR);
			} else {
				Log::write("SQL OK");
			}
		} else {
			$result = self::$connection->query($sql);
			if (self::$connection->error) {
				Log::write("Query ERROR: " . self::$connection->error, LogLevel::ERROR);
			} else {
				Log::write("Query OK");
			}
		}


		return $result;
	}

	public static function saveAssetToDatabase(Asset $asset)
	{



		if ($asset->id) {
			Log::write("Updating Asset with id: " . $asset->id);

			// Base Asset
			$sql = "UPDATE Asset SET assetName=?,assetActive=?,assetUrl=?,assetThumbnailUrl=?,assetDate=?,licenseId=?,typeId=?,creatorId=?,lastSuccessfulValidation=? WHERE assetId = ?";
			$parameters = [$asset->name, $asset->status->value, $asset->url, $asset->thumbnailUrl, $asset->date, $asset->license->value, $asset->type->value, $asset->creator->value, $asset->lastSuccessfulValidation, $asset->id];
			Database::runQuery($sql, $parameters);

			// Tags
			Database::runQuery("DELETE FROM Tag WHERE assetId = ?", [$asset->id]);
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES (?,?);";
				$parameters = [$asset->id, $tag];
				Database::runQuery($sql, $parameters);
			}

			// Quirks

			Database::runQuery("DELETE FROM Quirk WHERE assetId = ?", [$asset->id]);
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES (?,?);";
				$parameters = [$asset->id, $quirk->value];
				Database::runQuery($sql, $parameters);
			}
		} else {
			Log::write("Inserting new asset with url:" . $asset->url);

			// Base Asset
			$sql = "INSERT INTO Asset (assetId, assetActive,assetName, assetUrl, assetThumbnailUrl, assetDate, assetClicks, licenseId, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$parameters = [$asset->status->value, $asset->name, $asset->url, $asset->thumbnailUrl, $asset->date, 0, $asset->license->value, $asset->type->value, $asset->creator->value];
			Database::runQuery($sql, $parameters);

			// Tags
			foreach ($asset->tags as $tag) {
				$sql = "INSERT INTO Tag (assetId,tagName) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url, $tag];
				Database::runQuery($sql, $parameters);
			}

			// Quirks
			foreach ($asset->quirks as $quirk) {
				$sql = "INSERT INTO Quirk (assetId,quirkId) VALUES ((SELECT assetId FROM Asset WHERE assetUrl=?),?);";
				$parameters = [$asset->url, $quirk->value];
				Database::runQuery($sql, $parameters);
			}
		}


		return $asset;
	}

	public static function addAssetClickById(int $assetId)
	{
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		Database::runQuery($sql, [intval($assetId)]);
	}
}
