<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var int[] $byErased */
/** @var int[] $bySmartData */
/** @var int[] $byCapacity */
/** @var int[] $withoutErased */
/** @var array[] $formAndRotation */
/** @var int[] $surfaceScan */
/** @var bool $startDateSet */
/** @var array[] $byTypeFrequency */
/** @var array[] $missingSmartOrSurfaceScan */
/** @var array[] $failedSmartOrSurfaceScan */
$this->layout('main', ['title' => 'Stats: HDDs', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'hdds']);

$rollupTd = function(array $row, string $feature, &$emptyCounter) {
	if($row[$feature] === null) {
		$emptyCounter++;
		return '<td class="empty"></td>';
	} else {
		$printable = $this->printFeature($feature, $row[$feature], $lang ?? 'en');
		return "<td>$printable</td>";
	}
};
?>
<div class="row">
<?php if(!empty($surfaceScan)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by surface scan</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Surface scan</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($surfaceScan as $num => $count): ?>
				<tr>
					<td><?=$this->printFeature('surface-scan', $num, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($bySmartData)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by smart data</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Smart Data</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($bySmartData as $num => $count): ?>
				<tr>
					<td><?=$this->printFeature('smart-data', $num, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
	<?php if(!empty(krsort($byCapacity))): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by capacity</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Capacity</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byCapacity as $num => $count): ?>
				<tr>
					<td><?=$this->printFeature('capacity-decibyte', $num, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($formAndRotation)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs by Form factor and Rotation speed</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Form Factor</th>
				<th scope="col">Frequency</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($formAndRotation as $row):
				// We need to count empty cells before printing the td...
				$counter = 0;
				$td = $rollupTd($row, 'hdd-form-factor', $counter);
				$td .= $rollupTd($row, 'spin-rate-rpm', $counter);
				$td .= "<td>${row['Quantity']}</td>";

				if($counter > 0):
					if($counter === 3):
						$last = 'last';
					else:
						$last = '';
					endif;
					echo "<tr class=\"total $last\">$td</tr>";
				else:
					echo "<tr>$td</tr>";
				endif;

			endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($byErased)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs erased</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byErased as $num => $count): ?>
				<tr>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
	<?php if(!empty($withoutErased)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs without erased</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
				<tr>
					<td><?=$withoutErased['hdd']?></td>
				</tr>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($failedSmartOrSurfaceScan)): ?>
	<div class="stats list col-12">
		<p>HDDs with failed SMART or Surface Scan (<?=count($failedSmartOrSurfaceScan)?>, max 200)</p>
		<div>
			<?php foreach($failedSmartOrSurfaceScan as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
<?php if(!empty($missingSmartOrSurfaceScan)): ?>
<div class="stats list col-12">
	<p>HDDs with missing SMART or Surface Scan (<?=count($missingSmartOrSurfaceScan)?>, max 200)</p>
	<div>
		<?php foreach($missingSmartOrSurfaceScan as $item): ?>
			<a href="/item/<?=$item?>"><?=$item?></a>
		<?php endforeach ?>
	</div>
</div>
<?php endif ?>
