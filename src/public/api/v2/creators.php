<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
header("content-type: application/json");

$creators = [];

foreach (CREATOR::cases() as $c) {
	$creators []= [
		"id" => $c->value,
		"slug" => $c->slug(),
		"name" => $c->name(),
		"description" => $c->description(),
	];
}

echo json_encode($creators);