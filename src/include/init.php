<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

// Register autoloader
spl_autoload_register(function ($class) {
	require_once str_replace("\\", '/', __DIR__ . "/$class.php");
});
