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
		<link rel="stylesheet" href="/css/main.css">
	</head>

    <body >
		<header id="mainHeader" hx-get="/render/header.html" hx-trigger="load" hx-swap="outerHTML"></header>
		<nav id="asset-filters">
			<form 
				onchange="window.scrollTo(0,0);"
				hx-get="/render/asset-list.php" 
				hx-target="#asset-list" 
				hx-trigger="change,load" 
				hx-swap="innerHTML"
			>
				<select name="creator[]" multiple>
					<?php foreach(CREATOR::cases() as $c){ ?>
						<option value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>
				<select name="type[]" multiple>
				<?php foreach(TYPE::cases() as $c){ ?>
						<option value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>
				<select name="license[]" multiple>
				<?php foreach(LICENSE::cases() as $c){ ?>
						<option value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>
				<select name="avoid[]" multiple>
				<?php foreach(QUIRK::cases() as $c){ ?>
						<option value="<?=$c->slug()?>"><?=$c->name()?></option>
					<?php } ?>
				</select>
			</form>
		</nav>
		<main id="asset-list"></main>
	</body>
</html>