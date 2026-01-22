<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use asset\ScrapedAssetStatus;
use asset\AssetSorting;
use creator\Creator;


?>

<!DOCTYPE html>
<html lang="en">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/../components/head.php'; ?>

<body>
	<link rel="stylesheet" href=https://unpkg.com/chota />
	<form hx-get="/render/editor-list.php"
		hx-target="main"
		hx-trigger="submit,change,load,input delay:200ms"
		hx-swap="innerHTML"
		style="display: flex;">
		<select name="creator[]">
			<option value></option>
			<?php foreach (Creator::cases() as $c) { ?>
				<option class="form-option" value="<?= $c->slug() ?>"><?= $c->title() ?></option>
			<?php } ?>
		</select>
		<input type="number" name="id[]" placeholder="assetId">
		<select name="sort">
			<?php foreach (AssetSorting::cases() as $c) { ?>
				<option class="form-option" value="<?= $c->value ?>"><?= $c->value ?></option>
			<?php } ?>
		</select>
		<select name="status">
			<option value></option>
			<?php foreach (ScrapedAssetStatus::cases() as $c) { ?>
				<option class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
			<?php } ?>
		</select>
		<input type="number" name="offset" placeholder="offset">
		<button type="submit">Reload</button>
	</form>
	<hr>
	<main>

	</main>
</body>

</html>