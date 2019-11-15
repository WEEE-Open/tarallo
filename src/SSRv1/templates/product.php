<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Product $product */
/** @var string|null $add */
/** @var string|null $edit */
$features = $product->getFeatures();

// Until proven guilty, er, true
$adding = false;
$editing = false;

$summary = \WEEEOpen\Tarallo\SSRv1\Summary\Summary::peel($product);

?>

<article class="item root <?=$editing ? ' head editing' : ''?>">
	<header>
		<h2 id="code-<?=$this->e($product->getBrand())?>-<?=$this->e($product->getModel())?>-<?=$this->e($product->getVariant())?>"><?=$this->e($product->getBrand())?> <?=$this->e($product->getModel())?> <?=$this->e($product->getVariant())?></h2>
	</header>

	<nav class="itembuttons" data-for-item="<?=$this->e($product->getCode())?>">
		<?php if($editing): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><button class="delete">❌&nbsp;Delete</button>
		<?php elseif($adding): ?>
            <button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button>
		<?php else: ?>
			<button class="edit">🛠️&nbsp;Edit</button><button class="clone">🔲&nbsp;Copy</button><button class="history">📖&nbsp;History</button>
		<?php endif ?>
	</nav>
	<?php if($summary !== null && (!$editing || !$adding)): ?>
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
<?php if($editing): /* newProduct activates the editor on its own */ ?>
	<script>const activate = true;</script>
	<?php $this->insert('editor');
endif;
?>