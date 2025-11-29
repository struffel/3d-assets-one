<?php

use asset\AssetQuery;
use asset\AssetStatus;
use asset\License;
use asset\Quirk;
use asset\Type;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

$query = AssetQuery::fromHttpGet(filterStatus: NULL);
$assets = $query->execute();
?>

<?php foreach ($assets->assets as $a) { ?>

	<form hx-post="/render/editor-update.php"
		hx-target="#update-output-<?= $a->id ?>"
		hx-swap="innerHTML"
		style="display: grid;grid-template-columns: 128px 256px 256px 256px 256px 128px 256px">
		<div>
			<a href="https://<?= implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2)); ?>/go?id=<?= $a->id ?>">
				<img class="asset-image" alt="<?= $a->name ?>" src="<?= $_ENV["3D1_UNCACHED"] ?>/thumbnail/128-JPG-FFFFFF/<?= $a->id ?>.jpg">
			</a>
		</div>
		<div>
			<input readonly type="text" name="id" value="<?= $a->id ?>">
			<input size="48" type="text" name="name" value="<?= $a->name ?>">
			<input size="48" type="text" name="tagString" value="<?= implode(" ", $a->tags) ?>">
		</div>
		<div>
			<input size="32" type="text" name="url" value="<?= $a->url ?>">
			<input size="32" type="text" name="thumbnailUrl" value="<?= $a->thumbnailUrl ?>">
			<input type="date" name="date" value="<?= $a->date ?>">
		</div>

		<div>
			<select name="status">
				<?php foreach (AssetStatus::cases() as $c) { ?>
					<option <?= $a->status == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
				<?php } ?>
			</select>

			<select name="type">
				<?php foreach (Type::cases() as $c) { ?>
					<option <?= $a->type == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
				<?php } ?>
			</select>

			<select name="license">
				<?php foreach (License::cases() as $c) { ?>
					<option <?= $a->license == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
				<?php } ?>
			</select>
		</div>
		<div>
			<select name="quirks[]" multiple>
				<?php foreach (Quirk::cases() as $c) { ?>
					<option <?= in_array($c, $a->quirks) ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
				<?php } ?>
			</select>
		</div>
		<div>
			<button type="submit">Update</button>
		</div>
		<div class="update-output" hx-ext="remove-me" id="update-output-<?= $a->id ?>"></div>
	</form>
	<hr>
<?php } ?>