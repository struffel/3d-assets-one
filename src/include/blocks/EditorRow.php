<?php

namespace blocks;

use asset\AssetType;
use asset\StoredAsset;
use asset\StoredAssetStatus;

class EditorRow
{
	public static function render(StoredAsset $asset, bool $updated = false)
	{
?>

		<form
			hx-ext="remove-me"
			hx-post="/admin/render/editor-update-asset.php"
			hx-swap="outerHTML">
			<div>
				<a href="https://<?= implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2)); ?>/go?id=<?= $asset->id ?>">
					<img class="asset-image" alt="<?= $asset->title ?>" src="<?= $asset->getThumbnailUrl(64, "JPG", "FFFFFF") ?>">
				</a>
				<input readonly type="text" name="id" value="<?= $asset->id ?>">
			</div>
			<div>
				<input size="48" type="text" name="title" value="<?= $asset->title ?>">
				<input size="48" type="text" name="tagString" value="<?= implode(" ", $asset->tags) ?>">
			</div>
			<div>
				<input size="32" type="text" name="url" value="<?= $asset->url ?>">
				<input type="datetime-local" name="date" value="<?= $asset->date->format('Y-m-d\TH:i') ?>">
			</div>

			<div>
				<select name="status">
					<?php foreach (StoredAssetStatus::cases() as $c) { ?>
						<option <?= $asset->status == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
					<?php } ?>
				</select>

				<select name="type">
					<?php foreach (AssetType::cases() as $c) { ?>
						<option <?= $asset->type == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
					<?php } ?>
				</select>
			</div>
			<div>
				<button type="submit">Update</button>
			</div>
			<?php if ($updated): ?>
				<div remove-me="2s" class="updated-message">Updated!</div>
			<?php endif; ?>
		</form>
<?php
	}
}
