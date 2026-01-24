<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use asset\ScrapedAssetStatus;
use asset\AssetSorting;
use asset\StoredAssetStatus;
use blocks\HeadBlock;
use creator\Creator;


?>

<!DOCTYPE html>
<html lang="en">
<?php HeadBlock::render(); ?>

<body>
	<form hx-get="/render/editor-list.php"
		hx-target="main"
		hx-trigger="submit,change,load,input delay:200ms"
		hx-swap="innerHTML"
		style="display: flex;">

		<!-- Creator -->
		<select name="creator[]">
			<option value></option>
			<?php foreach (Creator::cases() as $c) { ?>
				<option
					class="form-option"
					value="<?= $c->slug() ?>"
					<?= in_array($c->slug(), $_GET['creator'] ?? []) ? 'selected' : '' ?>>
					<?= $c->title() ?>
				</option>
			<?php } ?>
		</select>

		<!-- ID -->
		<input type="number" name="id[]" placeholder="assetId" value="<?= htmlspecialchars($_GET['id'][0] ?? "") ?>">

		<!-- Sorting -->
		<select name="sort">
			<?php foreach (AssetSorting::cases() as $c) { ?>
				<option
					class="form-option"
					value="<?= $c->value ?>"
					<?= ($_GET['sort'] ?? NULL) === $c->value ? 'selected' : '' ?>>
					<?= $c->value ?>
				</option>
			<?php } ?>
		</select>

		<!-- Status -->
		<select name="status">
			<option value></option>
			<?php foreach (StoredAssetStatus::cases() as $c) { ?>
				<option
					class="form-option"
					value="<?= $c->value ?>"
					<?= ($_GET['status'] ?? NULL) === $c->value ? 'selected' : '' ?>>
					<?= $c->name ?>
				</option>
			<?php } ?>
		</select>

	</form>
	<hr>
	<main>

	</main>
</body>

</html>