<?php

use misc\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

Auth::requireAuth();
phpinfo();
