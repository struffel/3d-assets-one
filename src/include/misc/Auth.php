<?php

namespace misc;

class Auth
{
	public static function requireAuth()
	{
		$authUser = 'admin';
		$authPass = $_ENV["3D1_ADMIN_TOKEN"];

		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		$hasSuppliedCredentials = ($_SERVER['PHP_AUTH_USER'] ?? false) && ($_SERVER['PHP_AUTH_PW'] ?? false);
		$isNotAuthenticated = (
			!$hasSuppliedCredentials ||
			$_SERVER['PHP_AUTH_USER'] != $authUser ||
			$_SERVER['PHP_AUTH_PW']   != $authPass
		);
		if ($isNotAuthenticated) {
			header('HTTP/1.1 401 Authorization Required');
			header('WWW-Authenticate: Basic realm="Access denied"');
			exit;
		}
	}
}
