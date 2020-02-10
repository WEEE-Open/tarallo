<?php
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|null $base */

$base = $base ?? null;
?>

<article class="product item new editing root head">
	<header>
		<h2>
		<label for="new-product-brand">Brand:</label><input class="newcode" maxlength="100" id="new-product-brand">
		<label for="new-product-model">Model:</label><input class="newcode" maxlength="100" id="new-product-model">
		<label for="new-product-variant">Variant:</label><input class="newcode" maxlength="100" id="new-product-variant">
		</h2>
		<?php if(isset($base) && $base instanceof \WEEEOpen\Tarallo\Product): ?>
			<div class="inline-alert alert-info" role="alert">ℹ️&nbsp;This is a copy of <span class="code">...</span></div>
		<?php unset($noticeFeature); endif; ?>
	</header>

	<nav class="itembuttons">
		<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button>
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

    <nav class="itembuttons secondary">
        <button class="removeemptyfeatures" title="Remove empty features">🧽&nbsp;Clean</button>
    </nav>
</article>
<script>const activate = true;</script>
<?php $this->insert('editor'); ?>
