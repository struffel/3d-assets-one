<?php

use database\Database;
use log\Log;
use log\LogLevel;

require_once __DIR__ . '/../include/init.php';

Log::start(logName: "update-popularity/" . Log::timestampHelper(), writeToStdout: true);

Log::write("Recalculating popularity scores for all assets...", LogLevel::INFO);

Database::updatePopularityScores();

Log::write("Popularity scores updated.", LogLevel::INFO);

Log::stop(true);
