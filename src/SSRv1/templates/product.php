<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Product $product */
/** @var bool $editing */
$features = $product->getFeatures();
$summary = \WEEEOpen\Tarallo\SSRv1\Summary\Summary::peel($product);
$brandModel = $this->e($product->getBrand()) . ' ' . $this->e($product->getModel());
$maybeVariant = rtrim(' ' . $this->e($product->getVariantOrEmpty()));

$this->layout(
	'main',
	[
		'title' => $brandModel . $maybeVariant,
		'user' => $user,
		'itembuttons' => true,
	]
);
?>

<article class="item root <?=$editing ? ' head editing' : ''?>" data-brand="<?=$this->e($product->getBrand())?>" data-model="<?=$this->e($product->getModel())?>" data-variant="<?=$this->e($product->getVariant())?>">
	<header>
		<h2 id="code-<?=$this->e($product->getBrand())?>-<?=$this->e($product->getModel())?>-<?=$this->e($product->getVariant())?>"><?= $brandModel ?><small><?= $maybeVariant ?></small></h2>
	</header>

	<nav class="itembuttons" data-for-product-brand="<?=$this->e($product->getBrand())?>" data-for-product-model="<?=$this->e($product->getModel())?>"  data-for-product-variant="<?=$this->e($product->getVariant())?>">
		<?php if($editing): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><button class="delete">❌&nbsp;Delete</button>
		<?php else: ?>
			<button class="edit">🛠️&nbsp;Edit</button><button class="clone" disabled>🔲&nbsp;Copy</button><button class="items">🖥&nbsp;Items</button><button class="history">📖&nbsp;History</button>
		<?php endif ?>
	</nav>

	<?php if($summary !== null && !$editing): ?>
        <section class="summary <?=$working?>">
            <span><?= $summary ?></span>
        </section>
    <?php endif; ?>

	<?php if($editing): ?>
		<section class="own features editing">
			<?php $this->insert('featuresEdit', ['features' => $features]); ?>
		</section>

		<section class="addfeatures">
			<label>Feature:
				<select class="allfeatures">
				</select></label><button>Add</button>
		</section>
	<?php else: ?>
		<section class="features">
			<?php $this->insert('features', ['features' => $features]) ?>
		</section>
	<?php endif ?>
</article>
<?php if($editing): ?>
	<script>const activate = true;</script>
	<?php $this->insert('editor');
endif;
?>