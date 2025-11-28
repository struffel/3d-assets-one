<?php

use asset\Type;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$types = [];

foreach (Type::cases() as $t) {
	$types[] = [
		"id" => $t->value,
		"slug" => $t->slug(),
		"name" => $t->name()
	];
}

echo json_encode($types);
