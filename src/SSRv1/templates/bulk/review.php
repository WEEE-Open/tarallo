<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var WEEEOpen\Tarallo\Product|WEEEOpen\Tarallo\Item $original */
/** @var string[] $superSummary */
/** @var int $bulkId */

if($original instanceof \WEEEOpen\Tarallo\Product) {
	$viewUrl = '/product/' . rawurlencode($original->getBrand()) . '/' . rawurlencode($original->getModel()) . '/' . rawurlencode($original->getVariant());
	$whatever = $superSummary[0] === '' ? 'product' : $superSummary[0];
} else {
	$viewUrl = '/item/' . rawurlencode($original->getCode() ?? '');
	$whatever = $superSummary[0] === '' ? 'item' : $superSummary[0];
}
$whatever = $this->e($whatever);

$this->insert('bulk::menu', ['currentPage' => 'import']);
$this->layout('main', ['title' => 'Review ' . $superSummary[0], 'user' => $user, 'currentPage' => 'bulk import', 'container' => true]);


?>
<div class="alert alert-danger my-2" role="alert">
	<?= "Duplicate $whatever detected!" ?> <strong><?= $this->e($superSummary[1]) ?></strong> already exists.
</div>
<div class="text-center">
	<form method="post" action="/bulk/import">
		<a class="btn btn-outline-primary" type="button" href="<?=$this->e($viewUrl)?>">
			<i class="fa fa-cube"></i> View
		</a>
		<a class="btn btn-primary" type="button" href="/bulk/import/new/<?= (int) $bulkId ?>">
			Import anyway
		</a>
		<button class="btn btn-danger" type="submit"
				name="delete" value="<?= (int) $bulkId ?>">
			Delete
		</button>
	</form>
</div>

