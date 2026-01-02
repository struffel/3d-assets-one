<?php

use asset\Quirk;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$quirks = [];

foreach (Quirk::cases() as $q) {
	$quirks[] = [
		"id" => $q->value,
		"slug" => $q->slug(),
		"name" => $q->name()
	];
}

echo json_encode($quirks);
