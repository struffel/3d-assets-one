<?php

namespace misc;

use mysqli;
use mysqli_result;

class Database
{

	private static mysqli $connection;

	public static function initializeConnection()
	{
		Log::stepIn(__FUNCTION__);
		Log::write("Initializing DB Connection");


		// Create connection
		if (!isset(self::$connection)) {
			self::$connection = new mysqli(getenv("3D1_DB_SERVER"), getenv("3D1_DB_USERNAME"), getenv("3D1_DB_PASSWORD"));
			Log::write("Initialized DB connection to: " . getenv("3D1_DB_SERVER"));
		}

		// Check connection
		if (self::$connection->connect_error) {
			Log::write("Connection failed: " . self::$connection->connect_error, "SQL-ERROR");
		}

		$query = "use " . getenv("3D1_DB_NAME") . ";";
		self::$connection->query($query);
		Log::write("Selected DB: " . getenv("3D1_DB_NAME"));
		Log::stepOut(__FUNCTION__);
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
			Log::write("SQL execution ERROR: " . self::$connection->error, "SQL-ERROR");
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
			Log::write("SQL execution ERROR: " . self::$connection->error, "SQL-ERROR");
		} else {
			Log::write("SQL OK");
		}
	}

	public static function runQuery(string $sql, array $parameters = []): mysqli_result|bool
	{
		Log::stepIn(__FUNCTION__);
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
				Log::write("SQL execution ERROR: " . self::$connection->error, "SQL-ERROR");
			} else {
				Log::write("SQL OK");
			}
		} else {
			$result = self::$connection->query($sql);
			if (self::$connection->error) {
				Log::write("Query ERROR: " . self::$connection->error, "SQL-ERROR");
			} else {
				Log::write("Query OK");
			}
		}

		Log::stepOut(__FUNCTION__);
		return $result;
	}
}
