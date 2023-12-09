<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
?>

<!DOCTYPE html>
<html lang="en">
<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/head.php'; ?>
<body>
	<style>
		div{
			transition: all 1.0s;
		}
		.update-output.htmx-settling{
			background-color: lightblue;
		}
	</style>
	<link rel="stylesheet" href=https://unpkg.com/chota />
	<form	hx-get="/render/editor-list.php" 
			hx-target="main" 
			hx-trigger="change,load,input delay:200ms" 
			hx-swap="innerHTML"
			style="display: flex;"
	>
		<select name="creator[]">
			<option value></option>
			<?php foreach(CREATOR::cases() as $c){ ?>
				<option class="form-option" value="<?=$c->slug()?>"><?=$c->name()?></option>
			<?php } ?>
		</select>
		<input type="number" name="id[]" placeholder="assetId">
		<select name="sort">
			<?php foreach(SORTING::cases() as $c){ ?>
				<option class="form-option" value="<?=$c->value?>"><?=$c->value?></option>
			<?php } ?>
		</select>
		<select name="status">
			<option value></option>
			<?php foreach(ASSET_STATUS::cases() as $c){ ?>
				<option class="form-option" value="<?=$c->value?>"><?=$c->name?></option>
			<?php } ?>
		</select>
		<input type="number" name="offset" placeholder="offset">
	</form>
	<hr>
	<main>

	</main>
</body>
</html>