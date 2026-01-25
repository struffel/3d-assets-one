<?php

use creator\Creator;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
header("content-type: application/json");

$creators = [];

foreach (Creator::cases() as $c) {
	$creators[] = [
		"id" => $c->value,
		"slug" => $c->slug(),
		"name" => $c->title(),
		"license" => $c->commonLicense()->slug(),
		"description" => $c->description(),
	];
}

echo json_encode($creators);
