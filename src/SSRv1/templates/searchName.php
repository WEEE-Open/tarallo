<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $searchTerm */
/** @var int $limit */
/** @var \WEEEOpen\Tarallo\ItemCode|null $item */
/** @var array $brands */
/** @var array $products */
$this->layout('main', ['title' => 'Search ' . $this->e($searchTerm) . ' in names', 'user' => $user, 'currentPage' => 'search name']);
?>
<h5>Item <small>(exact match only)</small></h5>
<?php if(isset($item)): ?>
	<ul class="list-unstyled underlinelinks">
		<li><a href="/item/<?= $this->e($item->getCode()) ?>"><?= $this->e($item->getCode()) ?></a></li>
	</ul>
<?php else: ?>
	<p>No item named "<?= $this->e($searchTerm) ?>" found</p>
<?php endif ?>

<?php if(count($brands) > 0): ?>
	<h5>Brands <small>(<?= count($brands) ?>, max <?= (int) $limit ?>)</small></h5>
	<ul class="list-unstyled underlinelinks">
	<?php foreach($brands as $row): ?>
		<li><a href="/product/<?=$this->e(rawurlencode($row[0]))?>"><?= $this->e($row[0]) ?></a><?= $row[1] === 0 ? ' <small>(exact match)</small>' : '' ?></li>
	<?php endforeach ?>
	</ul>
<?php endif ?>

<?php if(count($products) > 0): ?>
	<h5>Products <small>(<?= count($products) ?>, max <?= (int) $limit ?>)</small></h5>
	<ul class="list-unstyled underlinelinks">
		<?php foreach($products as $row): $product = $row[0]; /** @var \WEEEOpen\Tarallo\ProductCode $product */ ?>
			<li><a href="/product/<?=$this->e(rawurlencode($product->getBrand()))?>/<?=$this->e(rawurlencode($product->getModel()))?>/<?=$this->e(rawurlencode($product->getVariant()))?>"><?= $this->e($product->getFullName()) ?></a><?= $row[1] === 0 ? ' <small>(exact match)</small>' : '' ?></li>
		<?php endforeach ?>
	</ul>
<?php endif ?>

<p class="text-muted"><button disabled class="btn btn-primary">Search in features</button> (not implemented yet)</p>
