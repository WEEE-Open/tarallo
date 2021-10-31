<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $cpusByBrand */
/** @var array $hddsByBrand */
/** @var array $itemsByColor */
/** @var array $hddsCapacity */
/** @var array $ramsCapacity */
/** @var array $itemWithAndWithoutSerialNumber */
$this->layout('main', ['title' => 'Stats: cool', 'user' => $user, 'currentPage' => 'stats', 'tooltips' => true, 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'cool']);
?>

<div class="row">
	<?php if (!empty($cpusByBrand)) : ?>
		<div class="col-12 col-xl-6">
			<table class="table table-borderless stats">
				<caption>CPUs count per brand</caption>
				<thead class="thead-dark">
				<tr>
					<th data-sortable="true" scope="col">Brand</th>
					<th data-sortable="true" scope="col">Count</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($cpusByBrand as $nameBrand => $count) : ?>
					<tr>
						<td><?=$this->e($nameBrand)?></td>
						<td><?=$this->e($count)?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	<?php if (!empty($hddsByBrand)) : ?>
		<div class="col-12 col-xl-6">
			<table class="table table-borderless stats">
				<caption>HDDs count per brand</caption>
				<thead class="thead-dark">
				<tr>
					<th data-sortable="true" scope="col">Brand</th>
					<th data-sortable="true" scope="col">Count</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($hddsByBrand as $nameBrand => $count) : ?>
					<tr>
						<td><?=$this->e($nameBrand)?></td>
						<td><?=$this->e($count)?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	<?php if (!empty($itemsByColor)) : ?>
		<div class="col-12 col-xl-6">
			<table class="table table-borderless stats">
				<caption>Items count by Color</caption>
				<thead class="thead-dark">
				<tr>
					<th data-sortable="true" scope="col">Color</th>
					<th data-sortable="true" scope="col">Count</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($itemsByColor as $nameColor => $count) : ?>
					<tr>
						<td><?=$this->e(ucfirst($nameColor))?></td>
						<td><?=$this->e(ucfirst($count))?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	<?php if (!empty($itemWithAndWithoutSerialNumber)) : ?>
		<div class="col-12 col-xl-6">
			<table class="table table-borderless stats">
				<caption>Items count by Serial Number</caption>
				<thead class="thead-dark">
				<tr>
					<th data-sortable="true" scope="col">Type</th>
					<th data-sortable="true" scope="col">With SN</th>
					<th data-sortable="true" scope="col">Without SN</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($itemWithAndWithoutSerialNumber as $type => $value) : ?>
					<tr>
						<td><?=$this->e(ucfirst($type))?></td>
						<td><?=array_key_exists('withSn', $value) ? $this->e(ucfirst($value['withSn'])) : 'None'?></td>
						<td><?=array_key_exists('withoutSn', $value) ? $this->e(ucfirst($value['withoutSn'])) : 'None'?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	<?php if (!empty($hddsCapacity) or !empty($ramsCapacity)) : ?>
		<div class="col-12 col-xl-6">
			<table class="table table-borderless stats">
				<caption>Total and average capacity of</caption>
				<thead class="thead-dark">
				<tr>
					<th data-sortable="true" scope="col">Type</th>
					<th data-sortable="true" scope="col">Total</th>
					<th data-sortable="true" scope="col">Average</th>
				</tr>
				</thead>
				<tbody>
					<tr>
						<td>HDDs</td>
						<td><?=$this->printFeature('capacity-decibyte', $hddsCapacity['totalCapacity'], $lang ?? 'en')?></td>
						<td><?=round((float) $this->printFeature('capacity-decibyte', (int) $hddsCapacity['averageCapacity'], $lang ?? 'en'), 1) . " " . preg_replace("/[^a-zA-Z]+/", "", $this->printFeature('capacity-decibyte', (int) $hddsCapacity['averageCapacity'], $lang ?? 'en'))?></td>
					</tr>
					<tr>
						<td>RAMs</td>
						<td><?=$this->printFeature('capacity-byte', $ramsCapacity['totalCapacity'], $lang ?? 'en')?></td>
						<td><?= round((float) $this->printFeature('capacity-byte', (int) $ramsCapacity['averageCapacity'], $lang ?? 'en'), 1) . " " . preg_replace("/[^a-zA-Z]+/", "", $this->printFeature('capacity-byte', (int) $ramsCapacity['averageCapacity'], $lang ?? 'en'))?></td>
					</tr>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</div>
