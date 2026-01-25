<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

use asset\ScrapedAssetStatus;
use asset\AssetSorting;
use asset\StoredAssetStatus;
use blocks\HeadBlock;
use blocks\LogoBlock;
use creator\Creator;
use misc\Auth;

Auth::requireAuth();
?>

<!DOCTYPE html>
<html lang="en">
<?php HeadBlock::render(); ?>
<link rel="stylesheet" href="/css/base.css">
<link rel="stylesheet" href="/css/page/editor.css">

<body>

	<form id="editor-form"
		hx-get="/admin/render/editor-list.php"
		hx-target="main"
		hx-trigger="submit,change,load,input delay:200ms"
		hx-swap="innerHTML">

		<!-- Title -->
		<div class="editor-header">
			<?php LogoBlock::render(); ?>
			Asset Editor
		</div>

		<!-- Creator -->
		<label for="creator-select">Creator:</label>
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
		<label for="id-input">Asset ID:</label>
		<input type="number" name="id[]" placeholder="assetId" value="<?= htmlspecialchars($_GET['id'][0] ?? "") ?>">

		<!-- Sorting -->
		<label for="sort-select">Sort by:</label>
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
		<label for="status-select">Status:</label>
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
		<hr>
	</form>
	<main class="editor-main">

	</main>
</body>

</html>