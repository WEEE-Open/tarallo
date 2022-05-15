<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $searchTerm */
/** @var int $limit */
/** @var \WEEEOpen\Tarallo\ItemCode|null $item */
/** @var array $brands */
/** @var string|null $normalizedAsBrand */
/** @var array $products */
/** @var array $itemFeatures */
/** @var array $productFeatures */
$this->layout('main', ['title' => 'Search ' . $this->e($searchTerm) . ' in names', 'user' => $user, 'currentPage' => 'search name', 'container' => true]);
?>
<h5>Item <small>(exact match only)</small></h5>
<?php if (isset($item)) : ?>
	<ul class="list-unstyled underlinelinks">
		<li><a href="/item/<?= $this->e($item->getCode()) ?>"><?= $this->e($item->getCode()) ?></a></li>
	</ul>
<?php else : ?>
	<p>No item named "<?= $this->e($searchTerm) ?>" found</p>
<?php endif ?>

<?php if (isset($normalizedAsBrand)) : ?>
	<h5>Similar brands</h5>
	<p class="underlinelinks">
		It seems <i><?= $this->e($searchTerm) ?></i> is a known brand, its normalized form is <a href="/product/<?=$this->e(rawurlencode($normalizedAsBrand))?>"><?= $this->e($normalizedAsBrand) ?></a>.
	</p>
<?php endif ?>

<?php if (count($brands) > 0) : ?>
	<h5>Similar brands <?= count($brands) === $limit ? ' <small>(max ' . $limit . ')</small>' : '' ?></h5>
	<ul class="list-unstyled underlinelinks">
	<?php foreach ($brands as $row) : ?>
		<li><a href="/product/<?=$this->e(rawurlencode($row[0]))?>"><?= $this->e($row[0]) ?></a><?= $row[1] === 0 ? ' <small>(exact match)</small>' : '' ?></li>
	<?php endforeach ?>
	</ul>
<?php endif ?>

<?php if (count($products) > 0) : ?>
	<h5>Products <?= count($products) === $limit ? ' <small>(max ' . $limit . ')</small>' : '' ?></h5>
	<ul class="list-unstyled underlinelinks">
		<?php foreach ($products as $row) :
			$product = $row[0]; /** @var \WEEEOpen\Tarallo\ProductCode $product */ ?>
			<li><a href="/product/<?=$this->e(rawurlencode($product->getBrand()))?>/<?=$this->e(rawurlencode($product->getModel()))?>/<?=$this->e(rawurlencode($product->getVariant()))?>"><?= $this->e($product->getFullName()) ?></a><?= $row[1] === 0 ? ' <small>(exact match)</small>' : '' ?></li>
		<?php endforeach ?>
	</ul>
<?php endif ?>

<?php if (count($itemFeatures) > 0) : ?>
	<h5>Item features<?= count($itemFeatures) === $limit ? ' <small>(max ' . $limit . ')</small>' : '' ?></h5>
	<ul class="list-unstyled underlinelinks">
		<?php foreach ($itemFeatures as $row) :
			$item = $row[0];
			$feature = $row[1];
			$ultra = $this->getUltraFeatures([$feature])[0]; /** @var \WEEEOpen\Tarallo\ItemCode $item */ /** @var \WEEEOpen\Tarallo\Feature $feature */ /** @var \WEEEOpen\Tarallo\SSRv1\UltraFeature $ultra */ ?>
			<li><a href="/item/<?= $this->e($item->getCode()) ?>"><?= $this->e($item->getCode()) ?></a><span class="text-muted">, </span><?= $this->e($ultra->pname) ?><span class="text-muted"> is </span><?= $this->e($ultra->pvalue) ?> <?= $row[2] === 0 ? ' <small class="text-muted">(exact match)</small>' : '' ?></li>
		<?php endforeach ?>
	</ul>
<?php endif ?>

<?php if (count($productFeatures) > 0) : ?>
	<h5>Product features<?= count($productFeatures) === $limit ? ' <small>(max ' . $limit . ')</small>' : '' ?></h5>
	<ul class="list-unstyled underlinelinks">
		<?php foreach ($productFeatures as $row) :
			$product = $row[0];
			$feature = $row[1];
			$printableProduct = $this->e($product->getBrand()) . ' ' . $this->e($product->getModel()) . rtrim(' ' . $this->e($product->getVariantOrEmpty()));
			$ultra = $this->getUltraFeatures([$feature])[0]; /** @var \WEEEOpen\Tarallo\ProductCode $product */ /** @var \WEEEOpen\Tarallo\Feature $feature */ /** @var \WEEEOpen\Tarallo\SSRv1\UltraFeature $ultra */ ?>
			<li><a href="/item/<?= $this->e($product->getBrand()) ?>/<?= $this->e($product->getModel()) ?>/<?= $this->e($product->getVariant()) ?>"><?= $printableProduct ?></a><span class="text-muted">, </span><?= $this->e($ultra->pname) ?><span class="text-muted"> is </span><?= $this->e($ultra->pvalue) ?> <?= $row[2] === 0 ? ' <small class="text-muted">(exact match)</small>' : '' ?></li>
		<?php endforeach ?>
	</ul>
<?php endif ?>
