<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $products */

$this->layout(
	'main',
	[
		'title' => 'All products',
		'user' => $user,
		'currentPage' => 'product list',
		'itembuttons' => false,
	]
);

$summarize = function() use (&$products) {
	$small = function(string $part) {
		if($part === '') {
			return '';
		} else {
			return "<small>$part</small>";
		}
	};

	foreach($products as &$row) {
		$product = $row[0];
		// TODO: provide these parts (this is nearly impossible with the current query)
		$rawSummary = \WEEEOpen\Tarallo\SSRv1\Summary\Summary::peelForList($product, null, null, null);
		$summary = [];
		$end = count($rawSummary) - 1;
		$i = 0;
		for($i = 0; $i < $end; $i++) {
			$part = $rawSummary[$i];
			if($i % 2 === 0) {
				$summary[] = $this->e($part);
			} else {
				$summary[] = $small($this->e($part));
			}
		}
		$summary = implode(' ', $summary);
		$row[] = $summary;
		// The for-loop stopped before this element
		$row[] = $small($this->e($rawSummary[$i]));
	}
};
$summarize();
?>
<h2>All products</h2>

<div class="row">
	<div class="col-12">
		<table class="table table-borderless stats">
			<thead class="thead-dark">
			<tr>
				<th scope="col">Product</th>
				<!--<td>Other names</td>-->
				<th scope="col">Items</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($products as $row): /** @var \WEEEOpen\Tarallo\ProductCode $product */ $product = $row[0]; $count = $row[1]; $summary = $row[2]; $aka = $row[3] ?>
				<tr>
					<td><a href="/product/<?=rawurlencode($product->getBrand())?>/<?=rawurlencode($product->getModel())?>/<?=rawurlencode($product->getVariant())?>"><?= $summary ?></small></a></td>
					<!--<td><= $row[3] ?></td>-->
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
</div>
