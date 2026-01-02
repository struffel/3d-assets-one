<?php

use creator\Creator;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
?>
<html>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/../components/head.php'; ?>

<body>
	<link rel="stylesheet" href="/css/page/about-creators.css">
	<?php include $_SERVER['DOCUMENT_ROOT'] . '/../components/header.php'; ?>
	<main>
		<?php foreach (Creator::cases() as $c) { ?>
			<div class="creator-box">
				<span>
					<img class="creator-box-image" src="/img/static/creator/<?= $c->value ?>.png">
				</span>
				<span>
					<h2><?= $c->name() ?></h2>
					<p><?= $c->description() ?></p>
					<p><a class="subtle-link" href="<?= $c->baseUrl() ?>">Main website</a></p>
				</span>
			</div>
		<?php } ?>
	</main>
	<?php include $_SERVER['DOCUMENT_ROOT'] . '/../components/footer.php'; ?>
</body>

</html>