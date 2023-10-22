<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';
	foreach (glob($_SERVER['DOCUMENT_ROOT'] . '/../include/*.php') as $file) {
		require_once $file;
	}