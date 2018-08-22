<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $serials */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $missingData */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $lost */
$this->layout('main', ['title' => 'Stats: items that need attention', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'attention']);
?>

<?php if(!empty($lost)): ?>
	<div class="statswrapper">
		<p>Most wanted, aka lost items (<?=count($lost)?>):</p>
		<div>
			<?php foreach($lost as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>

<?php if(!empty($missingData)): ?>
	<div class="statswrapper">
		<p>Items with missing data (<?=count($missingData)?>, max 100):</p>
		<div>
			<?php foreach($missingData as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>

<?php if(!empty($serials)): ?>
	<div class="statswrapper">
		<p>Duplicate serial numbers:</p>
		<table>
			<thead>
			<tr>
				<td>Serial</td>
				<td>Quantity</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($serials as $serial => $count): ?>
				<tr>
					<td><?=$serial?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>

