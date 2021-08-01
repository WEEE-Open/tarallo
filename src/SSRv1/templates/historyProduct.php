<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductCode $product */
/** @var string[][] $history */
/** @var bool $tooLong */
$title = $this->e($product->getFullName());

$this->layout('main', ['title' => $title . ' history', 'user' => $user]);
$bmv_rawurlencoded = $this->e(rawurlencode($product->getBrand()) . '/' . rawurlencode($product->getModel()) . '/' . rawurlencode($product->getVariant()));
?>

<article class="item root">
	<header>
		<h2 id="code-<?=$this->e($product->getBrand())?>-<?=$this->e($product->getModel())?>-<?=$this->e($product->getVariant())?>"><?= $title ?></h2>
	</header>


	<nav class="itembuttons row mx-2 mt-2 justify-content-end">
		<a class="btn btn-outline-primary btn-item col-sm-auto" role="button" href="/product/<?= $bmv_rawurlencoded ?>">
			<i class="fa fa-search"></i>&nbsp;View
		</a>
	</nav>

	<section class="history row">
		<div class="col-12">
			<?= $this->insert('historyEntries', ['history' => $history, 'tooLong' => $tooLong]) ?>
		</div>
	</section>
</article>
