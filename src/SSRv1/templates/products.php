<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $products */
/** @var string|null $brand */
/** @var string|null $model */

if($brand === null && $model === null) {
	$title = 'All products';
} elseif($brand !== null && $model === null) {
	$title = 'Products by ' . $this->e($brand);
} else {
	$title = 'Variants of ' . $this->e($brand ?? '') . ' ' . $this->e($model ?? '');
}

$this->layout(
	'main',
	[
		'title' => $title,
		'user' => $user,
		'currentPage' => 'product list',
	]
);

$summarize = function() use ($products) {
	$small = function(string $part) {
		if($part === '') {
			return '';
		} else {
			return "<small>$part</small>";
		}
	};

	$updated = [];
	foreach($products as $row) {
		$product = $row[0];
		$rawSummary = \WEEEOpen\Tarallo\SSRv1\Summary\Summary::peelForList($product, $row[1], $row[2], $row[3]);
		$summary = [];

		$end = count($rawSummary) - 1;
		for($i = 0; $i < $end; $i++) {
			$part = $rawSummary[$i];
			if($i % 2 === 0) {
				$summary[] = $this->e($part);
			} else {
				$summary[] = $small($this->e($part));
			}
		}
		$summary = implode(' ', $summary);
		// The for-loop stopped before this element
		$aka = $small($this->e($rawSummary[$i]));
		$new = [$product, $row[4], $summary, $aka];
		$updated[] = $new;
	}
	return $updated;
};
$products = $summarize();
?>
<h2><?= $title ?></h2>
<div class="row">
	<div class="col-12">
		<table class="table table-borderless stats table-hover">
			<thead class="thead-dark">
			<tr>
				<th scope="col">Product</th>
				<th scope="col">Other names</th>
				<th scope="col">Items</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($products as $row): /** @var \WEEEOpen\Tarallo\ProductCode $product */ $product = $row[0]; $count = $row[1]; $summary = $row[2]; $aka = $row[3] ?>
				<tr>
					<td><a href="/product/<?=$this->e(rawurlencode($product->getBrand()))?>/<?=$this->e(rawurlencode($product->getModel()))?>/<?=$this->e(rawurlencode($product->getVariant()))?>"><?= $summary ?></small></a></td>
					<td><?= $row[3] ?></td>
					<?php if($count === 0): ?>
					<td>0</td>
					<?php else: ?>
					<td><a href="/product/<?=$this->e(rawurlencode($product->getBrand()))?>/<?=$this->e(rawurlencode($product->getModel()))?>/<?=$this->e(rawurlencode($product->getVariant()))?>/items"><?= $count ?></a></td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>
