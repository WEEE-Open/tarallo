<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $locations */
/** @var int[] $recentlyAdded */
/** @var int[] $recentlyModified */
/** @var int[] $recentlyMoved */
$this->layout('main', ['title' => 'Stats', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => '']);
date_default_timezone_set('Europe/Rome');
?>

<div class="row">
<?php if(!empty($recentlyAdded)): ?>
	<div class="col-md-6">
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
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($recentlyModified)): ?>
	<div class="col-md-6">
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
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
	<?php if(!empty($recentlyMoved)): ?>
		<div class="col-md-6">
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
						<td><?=date('Y-m-d, H:i', $time)?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
<?php if(!empty($locations)): ?>
	<div class="col-md-6">
		<table class="table table-borderless stats">
			<caption>Items per location</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Location</th>
				<th scope="col">Items</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($locations as $row): ?>
				<tr>
					<td style="padding-left:<?=$this->e($row[0])?>rem"><a href="/item/<?=$this->e($row[1])?>?depth=1"><?=$this->e($row[1])?></a></td>
					<td><?=(int) $row[2]?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
</div>
