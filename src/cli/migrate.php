<?php

use database\Database;
use log\Log;
use log\LogLevel;
use log\LogResult;

require_once __DIR__ . '/../include/init.php';

Log::start(logName: "migrate/" . (new DateTime())->format('Y-m-d\TH-i-s-v'), level: LogLevel::DEBUG, writeToStdout: true);

Database::migrate();

Log::write("Finished migration.", [], LogLevel::FINISH_OK);
