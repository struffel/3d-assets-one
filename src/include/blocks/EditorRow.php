<?php

namespace blocks;

use asset\AssetType;
use asset\StoredAsset;
use asset\StoredAssetStatus;
use thumbnail\ThumbnailFormat;

class EditorRow
{
	public static function render(StoredAsset $asset, bool $updated = false)
	{
?>

		<form
			hx-ext="remove-me"
			hx-post="/admin/render/editor-update-asset.php"
			hx-swap="outerHTML"
			class="editor-row">

			<div class="editor-row-thumbnail">
				<img height="48" width="48" class="asset-image" alt="<?= $asset->title ?>" src="<?= $asset->getThumbnailUrl(ThumbnailFormat::JPG_64_FFFFFF) ?>">
			</div>
			<div class="editor-row-link">
				<a href="/go?id=<?= $asset->id ?>">
					Test
				</a>
			</div>
			<div class="editor-row-id">
				<input readonly type="text" name="id" value="<?= $asset->id ?>">
			</div>
			<div class="editor-row-title">
				<input type="text" name="title" value="<?= $asset->title ?>">
			</div>
			<div class="editor-row-tags">
				<input type="text" name="tagString" value="<?= implode(" ", $asset->tags) ?>">
			</div>
			<div class="editor-row-url">
				<input type="text" name="url" value="<?= $asset->url ?>">
			</div>
			<div class="editor-row-date">
				<input type="datetime-local" name="date" value="<?= $asset->date->format('Y-m-d\TH:i') ?>">
			</div>
			<div class="editor-row-status">
				<select name="status">
					<?php foreach (StoredAssetStatus::cases() as $c) { ?>
						<option <?= $asset->status == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="editor-row-type">
				<select name="type">
					<?php foreach (AssetType::cases() as $c) { ?>
						<option <?= $asset->type == $c ? 'selected' : '' ?> class="form-option" value="<?= $c->value ?>"><?= $c->name ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="editor-row-update">
				<button type="submit">Update</button>
			</div>
			<?php if ($updated): ?>
				<div remove-me="2s" class="updated-message">Updated!</div>
			<?php endif; ?>
		</form>
<?php
	}
}
