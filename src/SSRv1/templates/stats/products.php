<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $incomplete */
/** @var int[] $brandsProducts */
$this->layout('main', ['title' => 'Stats: products', 'user' => $user, 'currentPage' => 'stats', 'tooltips' => true, 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'products']);
?>

<div class="row">
<?php if (!empty($incomplete)) : ?>
	<div class="col-12 col-xl-6">
		<table class="table table-borderless stats">
			<caption>Items without a product, that could however have one</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Code</th>
				<th data-sortable="true" scope="col">Brand</th>
				<th data-sortable="true" scope="col">Model</th>
				<th data-sortable="true" scope="col">Variant</th>
				<th data-sortable="true" scope="col" data-tippy-content="Products that could be suitable for this item, i.e. available variants">Products</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($incomplete as $row) : ?>
				<tr>
					<td><a href="/item/<?= $this->e($row['Code']) ?>"><?=$this->e($row['Code'])?></a></td>
					<td><?=$this->e($row['Brand'])?></td>
					<td><?=$this->e($row['Model'])?></td>
					<td <?= ($row['Variant'] ?? '') === \WEEEOpen\Tarallo\ProductCode::DEFAULT_VARIANT ? 'class="text-muted"' : '' ?>><?=$this->e($row['Variant'] ?? '')?></td>
					<?php if ($row['NumVariants'] > 0) : ?>
						<td><a href="/product/<?= $this->e($row['Brand']) ?>/<?= $this->e($row['Model']) ?>"><?=$this->e($row['NumVariants'])?></a></td>
					<?php else : ?>
						<td>0</td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if (!empty($brandsProducts)) : ?>
	<div class="col-12 col-xl-6">
		<table class="table table-borderless stats">
			<caption>Products count per brand</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Brand</th>
				<th data-sortable="true" scope="col">Models</th>
				<th data-sortable="true" scope="col">Variants</th>
				<th data-sortable="true" scope="col" data-tippy-content="Variants per product">VPP</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($brandsProducts as $row) : ?>
				<tr>
					<td><a href="/product/<?= $this->e($row['Brand']) ?>"><?=$this->e($row['Brand'])?></a></td>
					<td><?=$this->e($row['Models'])?></td>
					<td><?=$this->e($row['Variants'] ?? '')?></td>
					<td><?=sprintf("%.2f", (double) $this->e($row['VPP']))?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
</div>
