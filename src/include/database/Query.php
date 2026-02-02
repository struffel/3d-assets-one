<?php

namespace Database;

use SQLite3Result;

class Query
{
	public function __construct(
		public string $sql,
		public array $params = []
	) {}

	public function execute(): SQLite3Result|bool
	{
		return Database::runQuery($this->sql, $this->params);
	}
}
