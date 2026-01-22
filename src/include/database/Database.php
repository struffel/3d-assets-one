<?php

namespace database;

use asset\Asset;
use indexing\event\IndexingEvent;
use log\Log;
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
			Log::write("Initialized DB connection", ["server" => $_ENV["3D1_DB_SERVER"], "username" => $_ENV["3D1_DB_USERNAME"]]);
		}

		// Check connection
		if (self::$connection->connect_error) {
			Log::write("Connection failed", ["error" => self::$connection->connect_error], LogLevel::ERROR);
		}

		$query = "use " . $_ENV["3D1_DB_NAME"] . ";";
		self::$connection->query($query);
		Log::write("Selected DB", ["database" => $_ENV["3D1_DB_NAME"]]);
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
			Log::write("SQL execution ERROR: ", self::$connection->error, LogLevel::ERROR);
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
			Log::write("SQL execution ERROR: ", self::$connection->error, LogLevel::ERROR);
		} else {
			Log::write("SQL OK");
		}
	}

	public static function runQuery(string $sql, array $parameters = []): mysqli_result|bool
	{

		Log::write("Received SQL query to run: ", ["sql" => $sql, "parameters" => $parameters]);

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
				Log::write("SQL execution ERROR: ", self::$connection->error, LogLevel::ERROR);
			} else {
				Log::write("SQL OK");
			}
		} else {
			$result = self::$connection->query($sql);
			if (self::$connection->error) {
				Log::write("Query ERROR: ", self::$connection->error, LogLevel::ERROR);
			} else {
				Log::write("Query OK");
			}
		}

		return $result;
	}

	public static function addAssetClickById(int $assetId)
	{
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		Database::runQuery($sql, [intval($assetId)]);
	}
}
