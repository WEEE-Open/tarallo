<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array[] $createdItems */
/** @var array[] $movedItems */
/** @var array[] $updatedItems */
/** @var array[] $overall */
$this->layout('main', ['title' => 'Stats: Users', 'user' => $user, 'currentPage' => 'stats', 'container' => true]);
$this->insert('stats::menu', ['currentPage' => 'users']);
?>

<div class="row">
<?php if(!empty($createdItems)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>Top users that have created items</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">User</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($createdItems as $user => $count): ?>
				<tr>
					<td><?=$user?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($movedItems)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>Top users that have moved items</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">User</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($movedItems as $user => $count): ?>
				<tr>
					<td><?=$user?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($updatedItems)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>Top users that have updated items</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">User</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($updatedItems as $user => $count): ?>
				<tr>
					<td><?=$user?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<?php if(!empty($overall)): ?>
	<div class="col-12 col-md-8 col-lg-6">
		<table class="table table-borderless stats">
			<caption>Top users overall</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">User</th>
				<th scope="col">Count</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($overall as $user => $count): ?>
				<tr>
					<td><?=$user?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>