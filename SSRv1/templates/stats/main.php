<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $locations */
/** @var int[] $recentlyAdded */
/** @var int[] $recentlyModified */
$this->layout('main', ['title' => 'Stats', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => '']);
date_default_timezone_set('Europe/Rome');
?>

<div class="statswrapperwrapper">
<?php if(!empty($recentlyAdded)): ?>
	<div class="statswrapper">
		<p>Recently added items:</p>
		<table>
			<thead>
			<tr>
				<td>Item</td>
				<td>Added</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($recentlyAdded as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($recentlyModified)): ?>
	<div class="statswrapper">
		<p>Recently modified items:</p>
		<table>
			<thead>
			<tr>
				<td>Item</td>
				<td>Modified</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($recentlyModified as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($locations)): ?>
	<div class="statswrapper">
		<p>Items per location:</p>
		<table>
			<thead>
			<tr>
				<td>Location</td>
				<td>Items</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($locations as $code => $count): ?>
				<tr>
					<td><?=$code?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
</div>
