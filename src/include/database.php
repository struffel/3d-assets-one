<?php

class DatabaseLogic{

	private static mysqli $connection;

	public static function initializeConnection(){
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Initializing DB Connection");
		

		// Create connection
		if(!isset(DatabaseLogic::$connection)){
			DatabaseLogic::$connection = new mysqli(getenv("3D1_DB_SERVER"), getenv("3D1_DB_USERNAME"), getenv("3D1_DB_PASSWORD"));
			LogLogic::write("Initialized DB connection to: ".getenv("3D1_DB_SERVER"));
		}
		
		// Check connection
		if (DatabaseLogic::$connection->connect_error) {
			LogLogic::write("Connection failed: " . DatabaseLogic::$connection->connect_error,"SQL-ERROR");
		}

		$query = "use ".getenv("3D1_DB_NAME").";";
		DatabaseLogic::$connection->query($query);
		LogLogic::write("Selected DB: ".getenv("3D1_DB_NAME"));
		LogLogic::stepOut(__FUNCTION__);
	}

	public static function runQuery(string $sql, array $parameters = []) : mysqli_result{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Received SQL query to run: ".$sql." (".implode(",",$parameters).")");
		
		if(!isset(DatabaseLogic::$connection)){
			DatabaseLogic::initializeConnection();
		}

		if(sizeof($parameters) > 0){
			$dataType = str_repeat('s',sizeof($parameters));
			$statement = DatabaseLogic::$connection->prepare($sql);
			if($statement){
				$statement->bind_param($dataType, ...$parameters);
				$statement->execute();
				$result = $statement->get_result();
				if(DatabaseLogic::$connection->error){
					LogLogic::write("Prepared statement execution ERROR: ".DatabaseLogic::$connection->error,"SQL-ERROR");
				}else{
					LogLogic::write("Prepared Statement OK");
				}
			}else{
				LogLogic::write("Prepared statement preparation ERROR: ".DatabaseLogic::$connection->error,"SQL-ERROR");
			}
		}else{
			$result = DatabaseLogic::$connection->query($sql);
			if(DatabaseLogic::$connection->error){
				LogLogic::write("Query ERROR: ".DatabaseLogic::$connection->error,"SQL-ERROR");
			}else{
				LogLogic::write("Query OK");
			}
		}
		
		LogLogic::stepOut(__FUNCTION__);
		return $result;
	}
}