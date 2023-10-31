<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
?>
<html>
	<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/head.php'; ?>
	<body>

		<style>
			.creator-box{
				margin: 25px;
			}
		</style>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/header.php'; ?>
		<main>
			<?php foreach (CREATOR::cases() as $c) { ?>
				<div class="creator-box">
					<h2><?=$c->name()?></h2>
					<p><?=$c->description()?></p>
					<p><a href="<?=$c->baseUrl()?>">Main website</a></p>
				</div>
			<?php } ?>
		</main>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/footer.php'; ?>
		</body>
</html>