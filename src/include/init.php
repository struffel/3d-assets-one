<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Register autoloader
spl_autoload_register(function ($class) {
	require_once str_replace("\\", '/', __DIR__ . "/$class.php");
});

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

// Register the media directory in the environment
$_ENV['3D1_MEDIA_DIRECTORY'] = __DIR__ . "/../public/img";
putenv("3D1_MEDIA_DIRECTORY=" . $_ENV['3D1_MEDIA_DIRECTORY']);
