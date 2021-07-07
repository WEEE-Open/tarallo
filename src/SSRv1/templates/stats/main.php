<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int[] $recentlyAdded */
/** @var int[] $recentlyModified */
/** @var int[] $recentlyMoved */
$this->layout('main', ['title' => 'Stats', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => '']);
date_default_timezone_set('Europe/Rome');
?>

<div class="row">
<?php if(!empty($recentlyAdded)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>Recently added items</caption>
			<thead class="thead-dark">
				<tr>
					<th scope="col">Item</th>
					<th scope="col">Added</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($recentlyAdded as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td class="small"><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($recentlyModified)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>Recently modified items</caption>
			<thead class="thead-dark">
				<tr>
					<th scope="col">Item</th>
					<th scope="col">Modified</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($recentlyModified as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td class="small"><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
	<?php if(!empty($recentlyMoved)): ?>
		<div class="col-md-6 col-xl-4">
			<table class="table table-borderless stats">
				<caption>Recently moved items</caption>
				<thead class="thead-dark">
				<tr>
					<th scope="col">Item</th>
					<th scope="col">Moved</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach($recentlyMoved as $code => $time): ?>
					<tr>
						<td><a href="/item/<?=$code?>"><?=$code?></a></td>
						<td class="small"><?=date('Y-m-d, H:i', $time)?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</div>
