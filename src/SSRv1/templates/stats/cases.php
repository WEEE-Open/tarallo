<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int[] $leastRecent */
/** @var int[] $mostRecent */
/** @var int[] $byOwner */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var string[] $ready */
$this->layout('main', ['title' => 'Stats: cases (computers)', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'cases']);
date_default_timezone_set('Europe/Rome');
$this->insert('stats::header', ['location' => $location, 'locationSet' => $locationSet, 'startDate' => $startDate, 'startDateSet' => $startDateSet]);
?>
<div class="row">
<?php if(!empty($ready)): ?>
	<div class="stats list col-12">
		<p>Ready computers (total <?= count($ready) ?>)</p>
		<div>
			<?php foreach($ready as $item): ?>
				<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
<?php if(!empty($leastRecent)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>30 computers where no work has been done in a long time</caption>
			<thead class="thead-dark">
				<tr>
					<th scope="col">Case</th>
					<th scope="col">Last action</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($leastRecent as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($mostRecent)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>30 computers where work has been done recently</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Case</th>
				<th scope="col">Last action</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($mostRecent as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($byOwner)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>Computers by owner<?php if($startDate !== null):?> (acquired after <?=$startDate->format('Y-m-d')?>)<?php endif; ?></caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Owner</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byOwner as $owner => $count): ?>
				<tr>
					<td><?=$owner?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($byMobo)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>Cases by motherboard form factor<?php if($startDate !== null):?> (acquired after <?=$startDate->format('Y-m-d')?>)<?php endif; ?></caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Form factor</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byMobo as $type => $count): ?>
				<tr>
					<td><?=$this->printFeature('motherboard-form-factor', $type, $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($byDate)): ?>
	<div class="col-md-6 col-xl-4">
		<table class="table table-borderless stats">
			<caption>Owner by date</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Owner</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byDate as $owner => $count): ?>
				<tr>
					<td><?=$owner?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
</div>
