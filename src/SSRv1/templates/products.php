<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $products */

$this->layout(
	'main',
	[
		'title' => 'All products',
		'user' => $user,
		'itembuttons' => false,
	]
);
?>
<h2>All products</h2>

<div class="tablewrapper">
	<table>
		<thead>
		<tr>
			<td>Product</td>
			<td>Items</td>
		</tr>
		</thead>
		<tbody>
		<?php foreach($products as $row): /** @var \WEEEOpen\Tarallo\ProductCode $product */ $product = $row[0]; $count = $row[1] ?>
			<tr>
				<td><a href="/product/<?=rawurlencode($product->getBrand())?>/<?=rawurlencode($product->getModel())?>/<?=rawurlencode($product->getVariant())?>"><?= $this->e($product->getBrand()) . ' ' . $this->e($product->getModel())?><small><?= rtrim(' ' . $this->e($product->getVariantOrEmpty())) ?></small></a></td>
				<?php if($count === 0): ?>
				<td>0</td>
				<?php else: ?>
				<td><a href="/product/<?=rawurlencode($product->getBrand())?>/<?=rawurlencode($product->getModel())?>/<?=rawurlencode($product->getVariant())?>/items"><?= $count ?></a></td>
				<?php endif ?>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>
