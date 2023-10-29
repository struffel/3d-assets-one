<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
header("HX-Replace-Url: ?about-creators");
?>
<style>
	.creator-box{
		margin: 25px;
	}
</style>
<?php foreach (CREATOR::cases() as $c) { ?>
	<div class="creator-box">
		<h2><?=$c->name()?></h2>
		<p><?=$c->description()?></p>
		<p><a href="<?=$c->baseUrl()?>">Main website</a></p>
	</div>
<?php } ?>