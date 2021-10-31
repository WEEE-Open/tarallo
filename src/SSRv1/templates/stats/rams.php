<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var array[] $byTypeFrequency */
/** @var array[] $byTypeSize */
/** @var int[] $byType */
/** @var int[] $byFormFactor */
/** @var \WEEEOpen\Tarallo\ItemCode[] $noFrequency */
/** @var \WEEEOpen\Tarallo\ItemCode[] $noSize */
/** @var bool $allowDateSelection */
$this->layout('main', ['title' => 'Stats: RAMs', 'user' => $user, 'currentPage' => 'stats', 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'rams']);
$this->insert('stats::header', [
	'location' => $location,
	'locationSet' => $locationSet,
	/*'startDate' => $startDate, 'startDateSet' => $startDateSet,*/
	'allowDateSelection' => false
]);

$rollupTd = function (array $row, string $feature, &$emptyCounter) {
	if ($row[$feature] === null) {
		$emptyCounter++;
		return '<td class="empty"></td>';
	} else {
		$printable = $this->printFeature($feature, $row[$feature], $lang ?? 'en');
		return "<td>$printable</td>";
	}
};
?>

<div class="row">
<?php if (!empty($byType)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
		<caption>RAMs by type/standard</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Type</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($byType as $type => $count) : ?>
				<tr>
					<td><?=$this->printFeature('ram-type', $type, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if (!empty($byFormFactor)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>RAMs by form factor</caption>
			<thead class="thead-dark">
			<tr>
				<th data-sortable="true" scope="col">Type</th>
				<th data-sortable="true" scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($byFormFactor as $type => $count) : ?>
				<tr>
					<td><?=$this->printFeature('ram-form-factor', $type, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if (!empty($byTypeSize)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>RAMs by type and size</caption>
			<thead class="thead-dark">
			<tr>
				<th  scope="col">Type</th>
				<th scope="col">Form Factor</th>
				<th scope="col">Capacity</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($byTypeSize as $row) :
				// We need to count empty cells before printing the td...
				$counter = 0;
				$td = $rollupTd($row, 'ram-type', $counter);
				$td .= $rollupTd($row, 'ram-form-factor', $counter);
				$td .= $rollupTd($row, 'capacity-byte', $counter);
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
<?php endif ?>
<?php if (!empty($byTypeFrequency)) : ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>RAMs by type and frequency</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Type</th>
				<th scope="col">Form Factor</th>
				<th scope="col">Frequency</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($byTypeFrequency as $row) :
				// We need to count empty cells before printing the td...
				$counter = 0;
				$td = $rollupTd($row, 'ram-type', $counter);
				$td .= $rollupTd($row, 'ram-form-factor', $counter);
				$td .= $rollupTd($row, 'frequency-hertz', $counter);
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
<?php if (!empty($noFrequency)) : ?>
	<div class="stats list col-12">
		<p>RAMs with unknown frequency <small>(<?=count($noFrequency)?>, max 200)</small></p>
		<div>
			<?php foreach ($noFrequency as $item) : ?>
				<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
<?php if (!empty($noSize)) : ?>
	<div class="stats list col-12">
		<p>RAMs with unknown size <small>(<?=count($noSize)?>, max 200)</small></p>
		<div>
			<?php foreach ($noSize as $item) : ?>
				<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
</div>
