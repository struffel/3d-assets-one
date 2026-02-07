<?php

use misc\Auth;
use blocks\AdminHeaderBlock;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

Auth::requireAuth();
AdminHeaderBlock::render();
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/css/base.css">
	<title>PHP info</title>
</head>

<body>
	<?php phpinfo(); ?>
</body>

</html>