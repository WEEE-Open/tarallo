<?php
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|\WEEEOpen\Tarallo\Item|null $base */
/** @var int $importedFrom|null */

$base = $base ?? null;
if($base instanceof \WEEEOpen\Tarallo\Item) {
	$baseItem = $base;
	$base = \WEEEOpen\Tarallo\Product::fromItem($base);
}
$isClone = $base instanceof \WEEEOpen\Tarallo\Product && !isset($importedFrom);
?>

<article class="container product item new editing root head <?= $isClone ? 'clone' : '' ?>">
	<header class="row">
		<h2 class="col-12">
		<label for="new-product-brand">Brand:</label><input value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getBrand() : '' ?>" maxlength="100" id="new-product-brand">
		<label for="new-product-model">Model:</label><input value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getModel() : '' ?>" maxlength="100" id="new-product-model">
		<label for="new-product-variant">Variant:</label><input value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getVariant() : '' ?>" maxlength="100" id="new-product-variant">
		</h2>
		<?php if(isset($base)): ?>
			<?php if(isset($baseItem)): ?>
				<div class="inline-alert alert-info" role="alert">
					<i class="fa fa-info-circle"></i>&nbsp;This product is split from <span class="text-monospace"><?= $this->e($baseItem->getCode()) ?></span>
				</div>
			<?php elseif($isClone): ?>
				<div class="inline-alert alert-info" role="alert">
					<i class="fa fa-info-circle"></i>&nbsp;This is a copy of <?= $this->e($base->getBrand()) . ' ' . $this->e($base->getModel()) . rtrim(' ' . $this->e($base->getVariantOrEmpty())) ?>
				</div>
			<?php ?>

			<?php elseif(isset($importedFrom)): ?>
				<div class="inline-alert alert-info" role="alert"><i class="fa fa-info-circle"></i>️&nbsp;This product is generated from a bulk import</span></div>
			<?php endif; ?>

		<?php endif; ?>
	</header>

	<nav class="itembuttons row mx-2 mt-2 justify-content-end">
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
			<select class="allfeatures">
			</select></label>
		<button>Add</button>
	</section>

	<nav class="itembuttons secondary row mx-2 mt-2 justify-content-end">
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto removeemptyfeatures" title="Remove empty features" role="button">
			<i class="fa fa-eraser"></i>&nbsp;Clean
		</button>
	</nav>
</article>
<script>const activate = true;</script>
<?php $this->insert('editor'); ?>
