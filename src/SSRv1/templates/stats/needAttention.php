<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int[] $serials */
/** @var \WEEEOpen\Tarallo\ItemCode[] $missingData */
/** @var \WEEEOpen\Tarallo\ItemCode[] $lost */
$this->layout('main', ['title' => 'Stats: items that need attention', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'attention']);
?>

<div class="row">
<?php if(!empty($lost)): ?>
	<div class="stats list col-12">
		<p>Most wanted, aka lost items (<?=count($lost)?> items, max 100 shown)</p>
		<div>
			<?php foreach($lost as $item): ?>
				<a href="/item/<?=$this->e($item)?>"><?=$this->e($item)?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>

<?php if(!empty($missingData)): ?>
	<div class="stats list col-12">
		<p>Items with missing data (<?=count($missingData)?> items, max 500 shown)</p>
		<div>
			<?php foreach($missingData as $item): ?>
				<a href="/item/<?=$this->e($item)?>"><?=$this->e($item)?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>

<?php if(!empty($serials)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
		<caption>Duplicate serial numbers</caption>
		<thead class="thead-dark">
		<tr>
			<th scope="col">Serial</th>
			<th scope="col">Quantity</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach($serials as $serial => $count): ?>
			<tr>
				<td><?=$this->e($serial)?></td>
				<td><?=$this->e($count)?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</div>
<?php endif ?>
</div>
