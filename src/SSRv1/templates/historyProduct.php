<?php

/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductCode $product */
/** @var string[][] $history */
/** @var bool $tooLong */
$title = $this->e($product->getFullName());

$this->layout('main', ['title' => $title . ' history', 'user' => $user]);
$bmv_rawurlencoded = $this->e(rawurlencode($product->getBrand()) . '/' . rawurlencode($product->getModel()) . '/' . rawurlencode($product->getVariant()));
?>

<article class="container item root">
	<header class="row">
		<h4 class="p-2 col-12 col-md m-0" id="code-<?=$this->e($product->getBrand())?>-<?=$this->e($product->getModel())?>-<?=$this->e($product->getVariant())?>"><?= $title ?></h4>
		<nav class="p-2 m-0 itembuttons inheader">
			<a class="btn btn-outline-secondary btn-sm btn-item" role="button" href="/product/<?= $bmv_rawurlencoded ?>">
				<i class="fa fa-search"></i>&nbsp;View
			</a>
		</nav>
	</header>

<!--	<nav class="itembuttons row mx-0 mt-2 justify-content-end">-->
<!--	</nav>-->

	<section class="history row">
		<div class="col-12">
			<?= $this->insert('historyEntries', ['history' => $history, 'tooLong' => $tooLong]) ?>
		</div>
	</section>
</article>
