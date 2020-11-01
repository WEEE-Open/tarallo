<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $brands */

$this->layout(
	'main',
	[
		'title' => 'All products by brand',
		'user' => $user,
		'currentPage' => 'product list',
	]
); ?>

<h2>All products</h2>
<p>Select a brand to view all products.</p>
<div class="row">
	<div class="col-12">
		<table class="table table-borderless stats table-hover">
			<thead class="thead-dark">
			<tr>
				<th scope="col">Brand</th>
				<th scope="col">Unique products</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($brands as $row): /** @var string $brand */ $brand = $row[0]; $count = $row[1]; $summary = $row[2]; ?>
				<tr>
					<td><a href="/product/<?=$this->e(rawurlencode($brand))?>"><?= $this->e($brand) ?></a></td>
					<?php if($count === 0): ?>
					<td>0</td>
					<?php else: ?>
					<td><?= $count ?></td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>
