<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var int[] byNcore */
/** @var int[] byIsa */
/** @var int[] commmonBrands */
/** @var bool $startDateSet */
/** @var array[] $byTypeFrequency */
$this->layout('main', ['title' => 'Stats: CPUs', 'user' => $user, 'currentPage' => 'stats', 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'cpus']);
$this->insert('stats::header', ['location' => $location, 'locationSet' => $locationSet, 'allowDateSelection' => false]);
?>

<div class="row">
<?php if(!empty($byNcore)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
		<caption>CPUs by number of cores</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Core number</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byNcore as $nCore => $count): ?>
				<tr>
					<td><?=$this->printFeature('core-n', $nCore, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($byIsa)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>CPUs by architecture</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Isa</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byIsa as $isa => $count): ?>
				<tr>
					<td><?=$this->printFeature('isa', $isa, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($commonModels)): ?>
<div class="col-12 col-md-8 col-lg-6">
	<table class="table table-borderless stats">
		<caption>CPUs by most common models</caption>
		<thead class="thead-dark">
		<tr>
			<th data-sortable="true" scope="col">Model</th>
			<th data-sortable="true" scope="col">Count</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach($commonModels as $models => $count): ?>
			<tr>
				<td><?=$this->printFeature('model', $models, $lang ?? 'en')?></td>
				<td><?=$count?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php endif; ?>
