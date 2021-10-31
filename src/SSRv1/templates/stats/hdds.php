<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var int[] $bySmartData */
/** @var int[] $byCapacity */
/** @var int $byErased */
/** @var int $withoutErased */
/** @var string[] $withoutErasedList */
/** @var array[] $formAndRotation */
/** @var int[] $surfaceScan */
/** @var bool $startDateSet */
$this->layout('main', ['title' => 'Stats: HDDs', 'user' => $user, 'currentPage' => 'stats', 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'hdds']);
$this->insert('stats::header', ['location' => $location, 'locationSet' => $locationSet, 'allowDateSelection' => false]);

$rollupTd = function (array $row, string $feature, &$emptyCounter) {
	if ($row[$feature] === null) {
		$emptyCounter++;
		return '<td class="empty"></td>';
	} else {
		$printable = $this->printFeature($feature, $row[$feature], $lang ?? 'en');
		return "<td>$printable</td>";
	}
};

$erasedSum = $byErased + $withoutErased;
$byErasedPercent = $erasedSum > 0 ? sprintf(" (%.1f %%)", $byErased / (double) $erasedSum  * 100) : '';
$withoutErasedPercent = $erasedSum > 0 ? sprintf(" (%.1f %%)", $withoutErased / (double) $erasedSum * 100) : '';
?>
<div class="row">
	<div class="stats list col-12">
		<p>HDDs to erase <small>(max 200 shown)</small></p>
		<div>
		<?php foreach ($withoutErasedList as $withoutErasedDisk) : ?>
			<a href="/item/<?= $this->e($withoutErasedDisk) ?>"><?= $this->e($withoutErasedDisk) ?></a>
		<?php endforeach; ?>
		</div>
		<div class="mt-1">
			<small><?= $withoutErased ?><?= $withoutErasedPercent ?> not erased and <?= $byErased ?><?= $byErasedPercent ?> erased.</small>
		</div>
	</div>
</div>
<div class="row">
<?php if (!empty($surfaceScan)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by surface scan</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Surface scan</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($surfaceScan as $num => $count) : ?>
				<tr>
					<td><?=$this->printFeature('surface-scan', $num, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if (!empty($bySmartData)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by smart data</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Smart Data</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($bySmartData as $num => $count) : ?>
				<tr>
					<td><?=$this->printFeature('smart-data', $num, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
	<?php if (!empty($byCapacity)) :
		krsort($byCapacity) ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by capacity</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Capacity</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($byCapacity as $num => $count) : ?>
				<tr>
					<td><?=$this->printFeature('capacity-decibyte', $num, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
<?php if (!empty($formAndRotation)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by Form factor and Rotation speed</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Form Factor</th>
				<th scope="col">Rotation speed</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($formAndRotation as $row) :
				// We need to count empty cells before printing the td...
				$counter = 0;
				$td = $rollupTd($row, 'hdd-form-factor', $counter);
				$td .= $rollupTd($row, 'spin-rate-rpm', $counter);
				$td .= "<td>${row['Quantity']}</td>";

				if ($counter > 0) :
					if ($counter === 3) :
						$last = 'last';
					else :
						$last = '';
					endif;
					echo "<tr class=\"total $last\">$td</tr>";
				else :
					echo "<tr>$td</tr>";
				endif;
			endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
