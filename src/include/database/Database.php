<?php

namespace database;

use asset\Asset;
use Exception;
use indexing\event\IndexingEvent;
use log\Log;
use log\LogLevel;
use SQLite3;
use SQLite3Result;

class Database
{

	private static SQLite3 $connection;

	private static function initializeConnection(bool $createIfNotExists = false)
	{
		// Create connection
		if (!isset(self::$connection)) {
			Log::write("Initializing DB Connection");
			$dbPath = $_ENV["3D1_DB_PATH"];
			self::$connection = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
			self::$connection->enableExceptions(true);
			self::$connection->busyTimeout(5000);
			Log::write("Initialized SQLite DB connection", ["database" => $dbPath]);

			if (self::getUserVersion() == 0) {
				Log::write("Database user version is 0, running initial migration.");
				self::migrate();
			}
		}
	}

	/**
	 * Reads the content of `./sql` and runs all .sql files whose name is greater than the current user version of the database in order and updates the user version.
	 * @return void 
	 */
	public static function migrate()
	{

		self::startTransaction();
		do {
			$ranMigrationStep = false;
			$currentVersion = self::getUserVersion();

			$potentialNextVersion = $currentVersion + 1;
			$potentialNextPath = __DIR__ . "/sql/migration_" . $potentialNextVersion . ".sql";

			if (file_exists($potentialNextPath)) {
				Log::write("Running migration step " . $potentialNextVersion);
				$sql = file_get_contents($potentialNextPath);
				self::$connection->exec($sql);
				self::runQuery("PRAGMA user_version = " . $potentialNextVersion . ";");
				$ranMigrationStep = true;
			} else {
				Log::write("No migration step found for version " . $potentialNextVersion . ", stopping migrations.");
			}
		} while ($ranMigrationStep);
		self::commitTransaction();

		// Ensure that the database file is writable by everyone.
		// This is necessary because the web server user needs write access, but the migration might be run by a different user from the CLI.
		chmod($_ENV["3D1_DB_PATH"], 0666);
		Log::write("Set database file permissions to 0666.");
	}

	private static function getUserVersion(): int
	{

		self::initializeConnection();

		$result = self::$connection->querySingle("PRAGMA user_version;");
		return intval($result);
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
		self::initializeConnection();
		Log::write("Start transaction...");
		self::$connection->exec("BEGIN TRANSACTION;");
	}

	public static function commitTransaction()
	{
		self::initializeConnection();
		Log::write("Commit transaction...");
		self::$connection->exec("COMMIT;");
	}

	public static function runQuery(string $sql, array $parameters = []): SQLite3Result|bool
	{
		Log::write("Received SQL query to run: ", ["sql" => $sql, "parameters" => $parameters]);
		self::initializeConnection();


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

			$stmt = self::$connection->prepare($sql);

			// Bind parameters (SQLite3 uses 1-based index for positional parameters)
			foreach ($parameters as $index => $value) {
				$type = self::getSqlite3Type($value);
				$stmt->bindValue($index + 1, $value, $type);
			}

			$result = $stmt->execute();
		} else {
			$result = self::$connection->query($sql);
		}

		return $result;
	}

	/**
	 * Determine the SQLite3 type constant for a given value
	 */
	private static function getSqlite3Type(mixed $value): int
	{
		if ($value === null) {
			return SQLITE3_NULL;
		} elseif (is_int($value)) {
			return SQLITE3_INTEGER;
		} elseif (is_float($value)) {
			return SQLITE3_FLOAT;
		} elseif (is_bool($value)) {
			return SQLITE3_INTEGER;
		} else {
			return SQLITE3_TEXT;
		}
	}

	public static function addAssetClickById(int $assetId)
	{
		$sql = "INSERT INTO Asset(AssetId,assetClicks) VALUES (?,1) ON DUPLICATE KEY UPDATE assetClicks = assetClicks+1;";
		Database::runQuery($sql, [$assetId]);
	}
}
