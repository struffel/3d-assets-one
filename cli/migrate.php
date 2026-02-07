<?php

use database\Database;
use log\Log;
use log\LogLevel;
use log\LogResult;

require_once __DIR__ . '/../include/init.php';

Log::start(logName: "migrate/" . Log::timestampHelper(), writeToStdout: true);

Database::migrate();

Log::stop(true);
