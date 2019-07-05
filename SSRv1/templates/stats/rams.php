<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var array[] $byTypeFrequency */
/** @var array[] $byTypeSize */
/** @var int[] $byType */
/** @var int[] $byFormFactor */
/** @var int[] $bySize */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $noWorking */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $noFrequency */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $noSize */
/** @var bool $allowDateSelection */
$this->layout('main', ['title' => 'Stats: RAMs', 'user' => $user, 'currentPage' => 'stats']);
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
	<?php if(!empty($byType) || !empty($byFormFactor)): ?>
		<div class="tablewrapper">
			<?php if(!empty($byType)): ?>
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
			<?php endif; if(!empty($byFormFactor)): ?>
				<p>RAMs by form factor:</p>
				<table>
					<thead>
					<tr>
						<td>Type</td>
						<td>Count</td>
					</tr>
					</thead>
					<tbody>
					<?php foreach($byFormFactor as $type => $count): ?>
						<tr>
							<td><?=$this->printFeature('ram-form-factor', $type, $lang ?? 'en')?></td>
							<td><?=$count?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php endif; if(!empty($bySize)): ?>
				<p>RAMs by size:</p>
				<table>
					<thead>
					<tr>
						<td>Type</td>
						<td>Count</td>
					</tr>
					</thead>
					<tbody>
					<?php foreach($bySize as $type => $count): ?>
						<tr>
							<td><?=$this->printFeature('capacity-byte', $type, $lang ?? 'en')?></td>
							<td><?=$count?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif ?>
	<?php if(!empty($byTypeFrequency)): ?>
		<div class="tablewrapper">
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
				<?php foreach($byTypeFrequency as $row):
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
	<?php if(!empty($byTypeSize)): ?>
		<div class="tablewrapper">
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
				<?php foreach($byTypeSize as $row):
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
	<?php if(!empty($noWorking)): ?>
		<div class="tablewrapper large">
			<p>Untested RAMs (<?=count($noWorking)?>, max 200):</p>
			<div>
				<?php foreach($noWorking as $item): ?>
					<a href="/item/<?=$item?>"><?=$item?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
	<?php if(!empty($noFrequency)): ?>
		<div class="tablewrapper large">
			<p>RAMs with unknown frequency (<?=count($noFrequency)?>, max 200):</p>
			<div>
				<?php foreach($noFrequency as $item): ?>
					<a href="/item/<?=$item?>"><?=$item?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
	<?php if(!empty($noSize)): ?>
		<div class="tablewrapper large">
			<p>RAMs with unknown size (<?=count($noSize)?>, max 200):</p>
			<div>
				<?php foreach($noSize as $item): ?>
					<a href="/item/<?=$item?>"><?=$item?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
</div>
