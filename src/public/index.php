<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
?><!DOCTYPE html>
<html lang="en">
	
	<head>
		<script src="https://unpkg.com/htmx.org@1.9.6" integrity="sha384-FhXw7b6AlE/jyjlZH5iHa/tTe9EpJ1Y55RjcgPbjeWMskSxZt1v9qkxLJWNJaGni" crossorigin="anonymous"></script>
		<title>3Dassets.one - The asset search engine</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="apple-touch-icon" sizes="180x180" href="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/favicon-16x16.png">
		<link rel="manifest" href="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/site.webmanifest">
		<link rel="mask-icon" href="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/safari-pinned-tab.svg" color="#5bbad5">
		<link rel="shortcut icon" href="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/favicon.ico">
		<meta name="msapplication-TileColor" content="#2d89ef">
		<meta name="msapplication-config" content="https://3d1-media.struffelproductions.com/file/3D-Assets-One/favicon/browserconfig.xml">
		<meta name="theme-color" content="#ffffff">
		<link rel="stylesheet" href="/css/index.css">
	</head>

	<body>
		<nav id="asset-filters">
		<header>
		<div class="logo">
			<span style="color:#CB6CE6;">3D</span><span>assets</span><span style="color:#7ED957;">.</span><span>one</span>
		</div>
		<nav class="navbar">
			<button hx-get="/render/about-creators.php" hx-target="main" hx-trigger="click">Assets</button>
			<button hx-get="/render/about-creators.php" hx-target="main" hx-trigger="click">Creators</button>
			<button hx-get="/render/about-site.php" hx-target="main" hx-trigger="click" >About</button>
		</nav>
	</header>
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
		</nav>
		<main></main>
	</body>
</html>