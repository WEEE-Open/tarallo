<?php
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|\WEEEOpen\Tarallo\Item|null $base */
/** @var int $importedFrom|null */

$base = $base ?? null;
$baseItem = $baseItem ?? null;
$bulkMatches = $bulkMatches ?? null;
if ($base instanceof \WEEEOpen\Tarallo\Item) {
	$baseItem = $base;
	$base = \WEEEOpen\Tarallo\Product::fromItem($base);
}
$isClone = !isset($baseItem) && $base instanceof \WEEEOpen\Tarallo\Product && !isset($importedFrom);
?>

<article class="container product item new editing root head <?= $isClone ? 'clone' : '' ?>">
	<header class="row">
		<div class="p-2 col m-0 row identity">
			<label for="new-product-brand" class="col p-0 m-0">Brand:</label><input class="col-12 form-control" value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getBrand() : '' ?>" maxlength="100" id="new-product-brand">
			<label for="new-product-model" class="col p-0 m-0">Model:</label><input class="col-12 form-control" value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getModel() : '' ?>" maxlength="100" id="new-product-model">
			<label for="new-product-variant" class="col p-0 m-0">Variant:</label><input class="col-12 form-control" value="<?= ($base instanceof \WEEEOpen\Tarallo\Product) ? $base->getVariant() : \WEEEOpen\Tarallo\ProductCode::DEFAULT_VARIANT ?>" maxlength="100" id="new-product-variant">
		</div>
		<?php if (isset($base)) : ?>
			<?php if (isset($baseItem)) : ?>
				<div class="inline-alert alert-info" role="alert">
					<i class="fa fa-info-circle"></i>&nbsp;This product is split from <span class="text-monospace"><?= $this->e($baseItem->getCode()) ?></span>
				</div>
				<?php if (!empty($bulkMatches)) : ?>
					<div class="inline-alert alert-warning" role="alert">
						<i class="fa fa-magic"></i>&nbsp;<strong>Matching product found in bulk import:</strong>
						<ul class="mb-0 mt-2 list-unstyled">
							<?php foreach ($bulkMatches as $match) : ?>
								<li class="my-1">
									<i class="fa fa-chevron-right text-muted"></i>
									<strong><?= $this->e($match['brand']) ?> <?= $this->e($match['model']) ?></strong>
									<span class="badge badge-info ml-1"><?= (int) $match['score'] ?>% match</span>
									<?php if (!empty($match['bulkIdentifier'])) : ?>
										<small class="text-muted">(batch: <em><?= $this->e($match['bulkIdentifier']) ?></em>)</small>
									<?php endif; ?>
									<a href="/bulk/import/new/<?= (int) $match['id'] ?><?= isset($baseItem) ? '?split=' . $this->e($baseItem->getCode()) : '' ?>" class="btn btn-sm btn-success ml-2 py-0">
										<i class="fa fa-download"></i> Use this bulk data
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			<?php elseif ($isClone) : ?>
				<div class="inline-alert alert-info" role="alert">
					<i class="fa fa-info-circle"></i>&nbsp;This is a copy of <?= $this->e($base->getFullName()) ?>
				</div>
			<?php endif; ?>

			<?php if (isset($importedFrom)) : ?>
				<div class="inline-alert alert-info" role="alert"><i class="fa fa-info-circle"></i>️&nbsp;This product is generated from a bulk import</div>
			<?php endif; ?>

		<?php endif; ?>
	</header>

	<nav class="itembuttons primary row mx-0 mt-2 justify-content-end">
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto mr-auto cancel" role="button">
			<i class="fa fa-arrow-circle-left"></i>&nbsp;Cancel
		</button>
		<button class="btn btn-success btn-item col-4 col-sm-auto save" role="button"
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
