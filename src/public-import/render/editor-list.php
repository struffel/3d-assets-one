<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';

$query = AssetQuery::fromHttpGet(filterStatus:NULL);
$query->includeQuirks = true;
$query->includeTags = true;
$assets = AssetLogic::getAssets($query);
?>

<table>

<?php foreach ($assets->assets as $a) { ?>

<tr id="<?=$a->id?>">
	<form>
		<td>
			<img class="asset-image" alt="<?=$a->name?>" src="https://3d1-media.struffelproductions.com/file/3D-Assets-One/thumbnail/128-JPG-FFFFFF/<?=$a->id?>.jpg">
		</td>
		<td>
			<input disabled type="text" name="id" value="<?=$a->id?>">
			<input size="48" type="text" name="name" value="<?=$a->name?>">
			<input size="48" type="text" name="tags" value="<?=implode(" ",$a->tags)?>">
		</td>
		<td>
			<input size="32" type="text" name="url" value="<?=$a->url?>">
			<input size="32" type="text" name="thumbnailUrl" value="<?=$a->thumbnailUrl?>">
			<input type="date" name="date" value="<?=$a->date?>">
		</td>

		<td>
			<select  name="status">
				<?php foreach(ASSET_STATUS::cases() as $c){ ?>
					<option <?= $a->status == $c ? 'selected' : '' ?> class="form-option" value="<?=$c->value?>"><?=$c->name?></option>
				<?php } ?>
			</select>

			<select  name="type">
				<?php foreach(TYPE::cases() as $c){ ?>
					<option <?= $a->type == $c ? 'selected' : '' ?> class="form-option" value="<?=$c->value?>"><?=$c->name?></option>
				<?php } ?>
			</select>
		
			<select  name="license">
				<?php foreach(LICENSE::cases() as $c){ ?>
					<option <?= $a->license == $c ? 'selected' : '' ?> class="form-option" value="<?=$c->value?>"><?=$c->name?></option>
				<?php } ?>
			</select>
		</td>
		<td>
			<select name="quirks" multiple>
				<?php foreach(QUIRK::cases() as $c){ ?>
					<option <?= in_array($c,$a->quirks) ? 'selected' : '' ?> class="form-option" value="<?=$c->value?>"><?=$c->name?></option>
				<?php } ?>
			</select>
		</td>
		<td>
			<button>Update</button><br>
			<a href="https://<?=implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2));?>/go?id=<?=$a->id?>">Test</a>
		</td>
	</form>
</tr>

<?php } ?>

</table>