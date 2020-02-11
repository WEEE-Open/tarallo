<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductCode $product */
/** @var \WEEEOpen\Tarallo\Item[] $items */

$title = $this->e($product->getBrand()) . ' ' . $this->e($product->getModel()) . rtrim(' ' . $this->e($product->getVariantOrEmpty()));

$this->layout('main', ['title' => "All $title", 'user' => $user]);
?>

<h2><?= $title ?> (<?= count($items) ?> items)</h2>

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
