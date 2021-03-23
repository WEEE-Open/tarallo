<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array[] $lastEntries */

$this->layout('main', ['title' => 'Stats: history', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'history']);

?>

<?php if(!empty($lastEntries)): ?>
	<div class="col-md-6 col-lg-4">
		<table class="table table-borderless stats">
			<caption>Recently added items</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Item</th>
				<th scope="col">Added</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($lastEntries as $code => $change): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=$change?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
