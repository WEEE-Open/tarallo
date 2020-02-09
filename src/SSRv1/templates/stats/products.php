<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $incomplete */
/** @var array $splittable */
/** @var int[] $brandsProducts */
$this->layout('main', ['title' => 'Stats: items that need attention', 'user' => $user, 'currentPage' => 'stats', 'tooltips' => true]);
$this->insert('stats::menu', ['currentPage' => 'products']);
?>

<div class="row">
<?php if(!empty($incomplete)): ?>
	<div class="col-12 col-xl-6">
		<table class="table table-borderless stats">
			<caption>Items without a product, that could however have one</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Code</th>
				<th scope="col">Brand</th>
				<th scope="col">Model</th>
				<th scope="col">Variant</th>
				<th scope="col" data-toggle="tooltip" data-placement="top" title="Products that could be suitable for this item, i.e. available variants">Products</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($incomplete as $row): ?>
				<tr>
					<td><a href="/item/<?= $this->e($row['Code']) ?>"><?=$this->e($row['Code'])?></a></td>
					<td><?=$this->e($row['Brand'])?></td>
					<td><?=$this->e($row['Model'])?></td>
					<td><?=$this->e($row['Variant'] ?? '')?></td>
					<?php if($row['NumVariants'] > 0): ?>
						<td><a href="/product/<?= $this->e($row['Brand']) ?>/<?= $this->e($row['Model']) ?>"><?=$this->e($row['NumVariants'])?></a></td>
					<?php else: ?>
						<td>0</td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($splittable)): ?>
	<div class="col-12 col-xl-6">
		<table class="table table-borderless stats">
			<caption>Items that can be split with a product</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Code</th>
				<th scope="col">Brand</th>
				<th scope="col">Model</th>
				<th scope="col">Variant</th>
				<th scope="col" data-toggle="tooltip" data-placement="top" title="Features that can be moved to a product">Features</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($splittable as $row): ?>
				<tr>
					<td><a href="/item/<?= $this->e($row['Code']) ?>"><?=$this->e($row['Code'])?></a></td>
					<td><?=$this->e($row['Brand'])?></td>
					<td><?=$this->e($row['Model'])?></td>
					<td><?=$this->e($row['Variant'] ?? '')?></td>
					<td><?=$this->e($row['Features'])?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>

<?php if(!empty($brandsProducts)): ?>
	<div class="col-12 col-xl-6">
		<table class="table table-borderless stats">
			<caption>Items without a product, that could however have one</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Brand</th>
				<th scope="col">Models</th>
				<th scope="col">Variants</th>
				<th scope="col" data-toggle="tooltip" data-placement="top" title="Variants per product">VPP</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($brandsProducts as $row): ?>
				<tr>
					<td><a href="/product/<?= $this->e($row['Brand']) ?>"><?=$this->e($row['Brand'])?></a></td>
					<td><?=$this->e($row['Models'])?></td>
					<td><?=$this->e($row['Variants'] ?? '')?></td>
					<td><?=sprintf("%.1f", (double) $this->e($row['VPP']))?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
</div>
