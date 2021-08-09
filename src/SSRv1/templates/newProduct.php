<?php
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|\WEEEOpen\Tarallo\Item|null $base */
/** @var int $importedFrom|null */

$base = $base ?? null;
if($base instanceof \WEEEOpen\Tarallo\Item) {
	$baseItem = $base;
	$base = \WEEEOpen\Tarallo\Product::fromItem($base);
}
$isClone = !isset($baseItem) && $base instanceof \WEEEOpen\Tarallo\Product && !isset($importedFrom);
?>

<article class="container product item new editing root head <?= $isClone ? 'clone' : '' ?>">
	<header class="row">
		<div class="col-12 row identity">
			<div class="form-group col-lg-4 col-12"><label for="new-product-brand" class="col-12">Brand:</label><input class="mx-1 col-12 form-control" value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getBrand() : '' ?>" maxlength="100" id="new-product-brand"></div>
			<div class="form-group col-lg-4 col-12"><label for="new-product-model" class="col-12">Model:</label><input class="mx-1 col-12 form-control" value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getModel() : '' ?>" maxlength="100" id="new-product-model"></div>
			<div class="form-group col-lg-4 col-12"><label for="new-product-variant" class="col-12">Variant:</label><input class="ml-1 col-12 form-control" value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getVariant() : \WEEEOpen\Tarallo\ProductCode::DEFAULT_VARIANT ?>" maxlength="100" id="new-product-variant"></div>
		</div>
		<?php if(isset($base)): ?>
			<?php if(isset($baseItem)): ?>
				<div class="inline-alert alert-info" role="alert">
					<i class="fa fa-info-circle"></i>&nbsp;This product is split from <span class="text-monospace"><?= $this->e($baseItem->getCode()) ?></span>
				</div>
			<?php elseif($isClone): ?>
				<div class="inline-alert alert-info" role="alert">
					<i class="fa fa-info-circle"></i>&nbsp;This is a copy of <?= $this->e($base->getFullName()) ?>
				</div>
			<?php ?>

			<?php elseif(isset($importedFrom)): ?>
				<div class="inline-alert alert-info" role="alert"><i class="fa fa-info-circle"></i>️&nbsp;This product is generated from a bulk import</span></div>
			<?php endif; ?>

		<?php endif; ?>
	</header>

	<nav class="itembuttons row mx-0 mt-2 justify-content-end">
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto mr-auto cancel" role="button">
			<i class="fa fa-arrow-circle-left"></i>&nbsp;Cancel
		</button>
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto save" role="button"
			<?= isset($importedFrom) ? 'data-importId="' . (int) $importedFrom . '"' : '' ?>>
			<i class="fa fa-save"></i>&nbsp;Save
		</button>
	</nav>

	<section class="own features editing">
		<?php
		$this->insert('featuresEdit', ['features' => $base->getOwnFeatures()]);
		?>
	</section>

	<section class="addfeatures">
		<label>Feature:
			<select class="allfeatures ml-2 form-control">
			</select></label>
		<button class="btn btn-primary ml-2">Add</button>
	</section>

	<nav class="itembuttons secondary row mx-0 mt-2 justify-content-end">
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto removeemptyfeatures" title="Remove empty features" role="button">
			<i class="fa fa-eraser"></i>&nbsp;Clean
		</button>
	</nav>
</article>
<script>const activate = true;</script>
<?php $this->insert('editor'); ?>
