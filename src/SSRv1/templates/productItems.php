<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductCode $product */
/** @var \WEEEOpen\Tarallo\Item[] $items */

$title = $this->e($product->getBrand()) . ' ' . $this->e($product->getModel()) . rtrim(' ' . $this->e($product->getVariantOrEmpty()));

$this->layout('main', ['title' => "All $title", 'user' => $user, 'tooltips' => isset($edit)]);
?>

<h2><?= $title ?> (<?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?>)</h2>

<?php
$copyQuery = http_build_query([
	'copy-brand' => $product->getBrand(),
	'copy-model' => $product->getModel(),
	'copy-variant' => $product->getVariant(),
], null, '&', PHP_QUERY_RFC3986);
?>
<a class="btn btn-outline-primary btn-item col-6 col-sm-4 col-md-auto" role="button" href="/new/item?<?= $this->e($copyQuery) ?>">
	<i class="fa fa-cube"></i>&nbsp;Create Item
</a>

<div class="results">
	<?php foreach($items as $item) {
		/* the editor is activated by item or newItem (called by item) */
		$parameters = ['item' => $item, 'recursion' => false];
		if(isset($add)) {
			$parameters['add'] = $add;
		} else if(isset($edit)) {
			$parameters['edit'] = $edit;
		}
		$this->insert('item', $parameters);
	}
	?>

</div>
<?php if(!isset($edit)):
	$this->insert('editor');
endif; ?>
