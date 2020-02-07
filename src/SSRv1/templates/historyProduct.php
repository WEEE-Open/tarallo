<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductCode $product */
/** @var string[][] $history */
/** @var bool $tooLong */
$title = $this->e($product->getBrand()) . ' ' . $this->e($product->getModel()) . rtrim(' ' . $this->e($product->getVariantOrEmpty()));

$this->layout('main', ['title' => $title . ' history', 'user' => $user, 'itembuttons' => true]);
?>

<article class="item root">
	<header>
		<h2 id="code-<?=$this->e($product->getBrand())?>-<?=$this->e($product->getModel())?>-<?=$this->e($product->getVariant())?>"><?= $title ?></h2>
	</header>

	<nav class="itembuttons" data-for-product-brand="<?=$this->e($product->getBrand())?>" data-for-product-model="<?=$this->e($product->getModel())?>"  data-for-product-variant="<?=$this->e($product->getVariant())?>">
		<button class="view">🔍&nbsp;View</button>
	</nav>

	<section class="history row">
		<?= $this->insert('historyEntries', ['history' => $history, 'tooLong' => $tooLong]) ?>
	</section>
</article>
