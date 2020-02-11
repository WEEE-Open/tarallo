<?php
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|null $base */

$base = $base ?? null;
?>

<article class="container product item new editing root head">
	<header class="col-12">
		<h2>
		<label for="new-product-brand">Brand:</label><input class="newcode" maxlength="100" id="new-product-brand">
		<label for="new-product-model">Model:</label><input class="newcode" maxlength="100" id="new-product-model">
		<label for="new-product-variant">Variant:</label><input class="newcode" maxlength="100" id="new-product-variant">
		</h2 class="col-12">
		<?php if(isset($base) && $base instanceof \WEEEOpen\Tarallo\Product): ?>
			<div class="inline-alert alert-info" role="alert"><i class="fa fa-info-circle"></i>&nbsp;This is a copy of <span class="text-monospace">...</span></div>
		<?php unset($noticeFeature); endif; ?>
	</header>

	<nav class="itembuttons row mx-2 mt-2 justify-content-end">
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto mr-auto cancel" role="button">
			<i class="fa fa-arrow-circle-left"></i>&nbsp;Cancel
		</button>
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto save" role="button">
			💾&nbsp;Save
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
			🧽&nbsp;Clean
		</button>
	</nav>
</article>
<script>const activate = true;</script>
<?php $this->insert('editor'); ?>
