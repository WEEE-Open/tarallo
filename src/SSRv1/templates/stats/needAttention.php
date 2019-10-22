<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int[] $serials */
/** @var \WEEEOpen\Tarallo\ItemCode[] $missingData */
/** @var \WEEEOpen\Tarallo\ItemCode[] $lost */
$this->layout('main', ['title' => 'Stats: items that need attention', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'attention']);
?>

<div class="statswrapperwrapper">
<?php if(!empty($lost)): ?>
	<div class="tablewrapper large">
		<p>Most wanted, aka lost items (<?=count($lost)?>, max 100):</p>
		<div>
			<?php foreach($lost as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>

<?php if(!empty($missingData)): ?>
	<div class="tablewrapper large">
		<p>Items with missing data (<?=count($missingData)?>, max 500):</p>
		<div>
			<?php foreach($missingData as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>

<?php if(!empty($serials)): ?>
	<div class="tablewrapper">
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
</div>
