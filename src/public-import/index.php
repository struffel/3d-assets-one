<?php

	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

	foreach (glob($_SERVER['DOCUMENT_ROOT'] . '/../creators/*.php') as $file) {
		require_once $file;
	}

	phpinfo();
?>