<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
?><!DOCTYPE html>
<html lang="en">
	<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/head.php'; ?>
	<body>
		<nav id="asset-filters">
			<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/header.php'; ?>
			<form 
				id="asset-filters-form"
				oninput="window.scrollTo(0,0);"
				onchange="window.scrollTo(0,0);"
				hx-get="/render/asset-list.php" 
				hx-target="main" 
				hx-trigger="change,load,input delay:200ms" 
				hx-swap="innerHTML"
				hx-push-url="/"
			>
				<label for="creator[]">Creator</label>
				<select size="<?=sizeof(CREATOR::cases())?>" id="multi-select-creator" class="multi-select" name="creator[]" multiple>
					<?php foreach(CREATOR::cases() as $c){ ?>
						<option <?=in_array($c->slug(),$_GET['creator']??[]) ? 'selected' : '' ?> class="multi-select-option" value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>

				<label for="type[]">Type</label>
				<select size="<?=sizeof(TYPE::cases())?>" class="multi-select" name="type[]" multiple>
					<?php foreach(TYPE::cases() as $c){ ?>
						<option <?=in_array($c->slug(),$_GET['type']??[]) ? 'selected' : '' ?> value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>

				<label for="license[]">License</label>
				<select size="<?=sizeof(LICENSE::cases())?>" class="multi-select" name="license[]" multiple>
					<?php foreach(LICENSE::cases() as $c){ ?>
						<option <?=in_array($c->slug(),$_GET['license']??[]) ? 'selected' : '' ?> value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>

				<!--<label for="avoid[]">Exclude</label>
				<select size="<?=sizeof(QUIRK::cases())?>" class="multi-select" name="avoid[]" multiple>
					<?php foreach(QUIRK::cases() as $c){ ?>
						<option <?=in_array($c->slug(),$_GET['avoid']??[]) ? 'selected' : '' ?> value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>-->

				<select name="sort">
					<?php foreach(SORTING::cases() as $c){ ?>
						<option <?=( ($_GET['sort']??'') === $c->value) ? 'selected' : '' ?> value="<?=$c->value?>"><?=ucfirst($c->value)?></option>
					<?php } ?>
				</select>

				<input type="text" name="q" value="<?=preg_replace('/[^a-zA-Z0-9, ]/','',$_GET['q']??'')?>" placeholder="Tags...">
			</form>
			<script>
				// Make select elements toggleable.
				document.querySelectorAll('select option').forEach(function (element) {
					element.addEventListener("mousedown", 
						function (e) {
							e.preventDefault();
							element.parentElement.focus();
							this.selected = !this.selected;
							document.getElementById('asset-filters-form').dispatchEvent(new Event('change'));
							return false;
						}, false );
				});
			</script>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/header.php'; ?>
		</nav>
		<main></main>
	</body>
</html>