<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int[] $serials */
/** @var array $splittable */
/** @var array[] $failedSmartOrSurfaceScan */
/** @var \WEEEOpen\Tarallo\ItemCode[] $missingData */
/** @var \WEEEOpen\Tarallo\ItemCode[] $lost */
$this->layout('main', ['title' => 'Stats: items that need attention', 'user' => $user, 'currentPage' => 'stats', 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'attention']);
?>

<div class="row">
	<?php if (!empty($missingData)) : ?>
		<div class="stats list col-12">
			<p>Items with missing data <small>(<?=count($missingData)?>, max 500 shown)</small></p>
			<div>
				<?php foreach ($missingData as $item) : ?>
					<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
</div>
<div class="row">
	<?php if (!empty($failedSmartOrSurfaceScan)) : ?>
		<div class="stats list col-12">
			<p>Working HDDs with failed SMART or Surface Scan: do they really work? <small>(<?=count($failedSmartOrSurfaceScan)?>, max 200 shown)</small></p>
			<div>
				<?php foreach ($failedSmartOrSurfaceScan as $item) : ?>
					<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
</div>
<div class="row">
	<?php if (!empty($lost)) : ?>
		<div class="stats list col-12">
			<p>Most wanted, aka lost items <small>(<?=count($lost)?>, max 100 shown)</small></p>
			<div>
				<?php foreach ($lost as $item) : ?>
					<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
</div>
<div class="row">
<?php if (!empty($splittable)) : ?>
	<div class="col-12 col-lg-6">
		<table class="table table-borderless stats">
			<caption>Items that can be split with a product</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Code</th>
				<th data-sortable="true" scope="col">Brand</th>
				<th data-sortable="true" scope="col">Model</th>
				<th data-sortable="true" scope="col">Variant</th>
				<th data-sortable="true" scope="col" data-tippy-content="Features that can be moved to a product">Features</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($splittable as $row) : ?>
				<tr>
					<td><a href="/item/<?= $this->e($row['Code']) ?>"><?=$this->e($row['Code'])?></a></td>
					<td><?=$this->e($row['Brand'])?></td>
					<td><?=$this->e($row['Model'])?></td>
					<td><?=$this->e(\WEEEOpen\Tarallo\ProductCode::variantOrEmpty($row['Variant'] ?? ''))?></td>
					<td><?=$this->e($row['Features'])?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if (!empty($serials)) : ?>
	<div class="col-12 col-lg-6">
		<table class="table table-borderless stats">
			<caption>Duplicate serial numbers</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Serial</th>
				<th data-sortable="true" scope="col">Quantity</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($serials as $serial => $count) : ?>
				<tr>
					<td><a href="/search/feature/sn/<?=$this->e($serial)?>"><?=$this->e($serial)?></a></td>
					<td><?=$this->e($count)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
</div>
