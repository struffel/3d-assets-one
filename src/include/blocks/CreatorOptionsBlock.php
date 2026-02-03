<?php

namespace blocks;

use asset\StoredAssetQuery;
use creator\Creator;
use Creator\CreatorLicenseType;

class CreatorOptionsBlock
{

	/**
	 * 
	 * @param array<int,int> $assetCountByCreator 
	 * @return void 
	 */
	public static function render(
		?array $assetCountByCreator = null
	): void {
		if ($assetCountByCreator === null) {
			$assetCountByCreator = StoredAssetQuery::assetCountByCreator();
		}
?>
		<select size="<?= sizeof(Creator::cases()) ?>" id="multi-select-creator" hx-swap-oob="true" class="multi-select" name="creator[]" multiple>
			<?php foreach (Creator::cases() as $c) {
				$selected = in_array($c->slug(), $_GET['creator'] ?? []);
			?>
				<option
					onmousedown="event.preventDefault();toggleOption(this);"
					class="form-option"
					id="creator-option-<?= $c->value ?>"
					<?= $selected ? 'selected' : '' ?>
					class="multi-select-option"
					value="<?= $c->slug() ?>"
					style="--creator-icon: url('/static/creator/<?= $c->value ?>.png');"
					data-count="<?= $assetCountByCreator[$c->value] ?? 0 ?>">
					<?= $c->title()  ?>
				</option>
			<?php } ?>
		</select>
<?php
	}
}
