<?php


use asset\AssetSorting;
use asset\AssetType;
use asset\StoredAssetQuery;
use blocks\FooterBlock;
use blocks\HeadBlock;
use blocks\HeaderBlock;
use creator\Creator;
use database\Database;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$assetCountByCreator = StoredAssetQuery::assetCountByCreator();

?>
<!DOCTYPE html>
<html lang="en">
<?php HeadBlock::render(); ?>

<body>
	<link rel="stylesheet" href="/css/page/index.css">

	<nav id="asset-filters">
		<?php HeaderBlock::render(); ?>
		<form
			id="asset-filters-form"
			oninput="window.scrollTo(0,0);"
			onchange="window.scrollTo(0,0);"
			hx-get="/render/asset-list.php"
			hx-target="main"
			hx-trigger="change,load,input delay:200ms"
			hx-swap="innerHTML"
			hx-push-url="/">
			<label class="form-label" for="sort">Tags</label>
			<input class="text-input" type="text" name="q" value="<?= preg_replace('/[^a-zA-Z0-9, ]/', '', $_GET['q'] ?? '') ?>" placeholder="tags...">

			<label class="form-label" for="creator[]">Site</label>
			<select size="<?= sizeof(Creator::cases()) ?>" id="multi-select-creator" class="multi-select" name="creator[]" multiple>
				<?php foreach (Creator::cases() as $c) : ?>
					<option
						class="form-option"
						<?= in_array($c->slug(), $_GET['creator'] ?? []) ? 'selected' : '' ?> class="multi-select-option"
						value="<?= $c->slug() ?>"
						style="--creator-icon: url('/static/creator/<?= $c->value ?>.png');"
						data-count="<?= $assetCountByCreator[$c->value] ?? 0 ?>">
						<?= $c->title()  ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label class="form-label" for="type[]">Type</label>
			<select size="<?= sizeof(AssetType::cases()) ?>" class="multi-select" name="type[]" multiple>
				<?php foreach (AssetType::cases() as $c) { ?>
					<option class="form-option" <?= in_array($c->slug(), $_GET['type'] ?? []) ? 'selected' : '' ?> value="<?= $c->slug() ?>"><?= $c->name() ?></option>
				<?php } ?>
			</select>

			<label class="form-label" for="sort">Sort by</label>
			<select name="sort">
				<?php foreach ([AssetSorting::POPULAR, AssetSorting::LATEST, AssetSorting::OLDEST, AssetSorting::RANDOM] as $c) { ?>
					<option class="form-option" <?= (($_GET['sort'] ?? '') === $c->value) ? 'selected' : '' ?> value="<?= $c->value ?>"><?= ucfirst($c->value) ?></option>
				<?php } ?>
			</select>
		</form>
		<script src="/js/index.js"></script>
		<?php FooterBlock::render(); ?>
	</nav>
	<main></main>
</body>

</html>