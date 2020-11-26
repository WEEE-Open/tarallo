<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var int[] $erased */
/** @var int[] $smartData */
/** @var int[] $withoutErased */
/** @var array[] $formAndRotation */
/** @var int[] $surfaceScan */
/** @var bool $startDateSet */
/** @var array[] $byTypeFrequency */
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
<?php if(!empty($smartData)): ?>
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
			<?php foreach($smartData as $num => $count): ?>
				<tr>
					<td><?=$this->printFeature('smart-data', $num, $lang ?? 'en')?></td>
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
			<caption>RAMs by type and frequency</caption>
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
<?php if(!empty($erased)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>HDDs erased</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($erased as $num => $count): ?>
				<tr>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
