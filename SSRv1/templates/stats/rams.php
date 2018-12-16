<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var array[] $byType */
/** @var array[] $bySize */
/** @var int[] $byType */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $noworking */
/** @var bool $allowDateSelection */
$this->layout('main', ['title' => 'Stats: RAMs', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'rams']);
$this->insert('stats::header', [
	'location' => $location,
	'locationSet' => $locationSet,
	/*'startDate' => $startDate, 'startDateSet' => $startDateSet,*/
	'allowDateSelection' => false
]);

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
<div class="statswrapperwrapper">
	<?php if(!empty($byType)): ?>
		<div class="statswrapper">
			<p>RAMs by type/standard:</p>
			<table>
				<thead>
				<tr>
					<td>Type</td>
					<td>Count</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach($byType as $type => $count): ?>
					<tr>
						<td><?=$this->printFeature('ram-type', $type, $lang ?? 'en')?></td>
						<td><?=$count?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	<?php if(!empty($byFeature)): ?>
		<div class="statswrapper">
			<p>RAMs by type and frequency</p>
			<table>
				<thead>
				<tr>
					<td>Type</td>
					<td>Form Factor</td>
					<td>Frequency</td>
					<td>Count</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach($byFeature as $row):
					// We need to count empty cells before printing the td...
					$counter = 0;
					$td = $rollupTd($row, 'ram-type', $counter);
					$td .= $rollupTd($row, 'ram-form-factor', $counter);
					$td .= $rollupTd($row, 'frequency-hertz', $counter);
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
	<?php if(!empty($bySize)): ?>
		<div class="statswrapper">
			<p>RAMs by type and size</p>
			<table>
				<thead>
				<tr>
					<td>Type</td>
					<td>Form Factor</td>
					<td>Capacity</td>
					<td>Count</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach($bySize as $row):
					// We need to count empty cells before printing the td...
					$counter = 0;
					$td = $rollupTd($row, 'ram-type', $counter);
					$td .= $rollupTd($row, 'ram-form-factor', $counter);
					$td .= $rollupTd($row, 'capacity-byte', $counter);
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
	<?php endif ?>
	<?php if(!empty($noworking)): ?>
		<div class="statswrapper large">
			<p>Untested rams (<?=count($noworking)?>, max 200):</p>
			<div>
				<?php foreach($noworking as $item): ?>
					<a href="/item/<?=$item?>"><?=$item?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
</div>
