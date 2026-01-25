<?php

use asset\CommonLicense;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$licenses = [];

foreach (CommonLicense::cases() as $l) {
	$licenses[] = [
		"id" => $l->value,
		"slug" => $l->slug(),
		"name" => $l->name()
	];
}

echo json_encode($licenses);
