<?php

use blocks\FooterBlock;
use blocks\HeadBlock;
use blocks\HeaderBlock;
use creator\Creator;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';
?>
<html>
<?php HeadBlock::render(); ?>

<body>
	<link rel="stylesheet" href="/css/page/about-creators.css">
	<?php HeaderBlock::render(); ?>
	<main>
		<?php foreach (Creator::cases() as $c) { ?>
			<div class="creator-box">
				<span>
					<img class="creator-box-image" src="/static/creator/<?= $c->value ?>.png">
				</span>
				<span>
					<h2><?= $c->title() ?></h2>
					<p><?= $c->description() ?></p>
					<p>
						<a class="subtle-link" href="<?= $c->baseUrl() ?>">Main website</a>
						<?php if ($c->licenseUrl()) { ?>
							| <a class="subtle-link" href="<?= $c->licenseUrl() ?>">License</a>
						<?php } ?>
					</p>
				</span>
			</div>
		<?php } ?>
	</main>
	<?php FooterBlock::render(); ?>
</body>

</html>