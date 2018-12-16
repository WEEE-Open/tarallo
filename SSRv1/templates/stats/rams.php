<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var string[][] $byType */
/** @var int[] $bySize */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $noworking */
/** @var bool $allowDateSelection */
$this->layout('main', ['title' => 'Rams Stats', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'rams']);
$this->insert('stats::header', [
	'location' => $location,
	'locationSet' => $locationSet,
	/*'startDate' => $startDate, 'startDateSet' => $startDateSet,*/
	'allowDateSelection' => false
]);
?>
<div class="statswrapperwrapper">
	<?php if(!empty($byFeature)): ?>
		<div class="statswrapper">
			<p>Rams by type</p>
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
				<?php foreach($byFeature as $row): ?>
					<tr>
						<td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('ram-type',
								$row['Type']), $lang ?? 'en')?></td>
						<td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('ram-form-factor',
								$row['FormFactor']), $lang ?? 'en')?></td>
						<td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('frequency-hertz',
								$row['Frequency']), $lang ?? 'en')?></td>
						<td><?=$row['Quantity']?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	<?php if(!empty($bySize)): ?>
		<div class="statswrapper">
			<p>Rams by size</p>
			<table>
				<thead>
				<tr>
					<td>Size</td>
					<td>Count</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach($bySize as $size => $count): ?>
					<tr>
						<td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('capacity-byte',
								$size), $lang ?? 'en')?></td>
						<td><?=$count?></td>
					</tr>
				<?php endforeach ?>
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
