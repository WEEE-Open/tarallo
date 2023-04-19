<?php
/** @var string $donations|null */
/** @var bool $canCreateNew|null */

$this->layout('main', ['title' => 'Donations', 'currentPage' => 'donation', 'tooltips' => true]);

$completed = [];

?>

<div class="row">
	<div class="itembuttons primary row mx-0 mt-2 justify-content-end w-100">
		<div class="row d-flex m-0 justify-content-between mr-auto">
			<h2 class="col-8 p-0">Donations</h2>
		</div>
		<?php if ($canCreateNew ?? false): ?>
		<a href="/donation/new" class="btn btn-outline-primary col-4 col-sm-auto edit mb-2 mr-2">
			<i class="fa fa-plus"></i>&nbsp;New
		</a>
		<? endif ?>
	</div>
	<?php if (isset($donations) && !empty($donations)) : ?>
		<div class="col-12">
			<table class="table table-borderless stats">
				<caption>Active donations</caption>
				<thead class="thead-dark">
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Location</th>
					<th scope="col">Date</th>
					<th scope="col">Progress</th>
					<th scope="col">Total Items</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($donations as $donation) : ?>
					<?php if($donation["isCompleted"]) {
						array_push($completed, $donation);
						continue;
					}?>
					<tr>
						<td><a href="/donation/<?=$donation["id"]?>"><?=$donation["name"]?></a></td>
						<td><?=$donation["location"] ?? "" ?></td>
						<td><?=$donation["date"] ?? ""?></td>
						<td style="display: grid; grid-template-columns: 1fr auto; align-items: center;">
							<div class="progress ml-2 mr-2" style="height: 5px;">
								<div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: <?=$donation["progress"]?>%;" aria-valuenow="<?=$donation["progress"]?>" aria-valuemin="0" aria-valuemax="100"></div>
							</div>
							<?=$donation["progress"]?>%
						</td>
						<td><?=$donation["totalItems"] ?? ""?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
			<?php if(count($donations) === count($completed)): ?>
				<i class="text-center d-block">No active donations</i>
			<? endif ?>
			<?php if(!empty($completed)): ?>
				<table class="table table-borderless stats">
					<caption>Completed donations</caption>
					<thead class="thead-dark">
					<tr>
						<th scope="col">Name</th>
						<th scope="col">Location</th>
						<th scope="col">Date</th>
						<th scope="col">Progress</th>
						<th scope="col">Total Items</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($completed as $donation) : ?>
						<tr>
							<td><a href="/donation/<?=$donation["id"]?>"><?=$donation["name"]?></a></td>
							<td><?=$donation["location"] ?? "" ?></td>
							<td><?=$donation["date"] ?? ""?></td>
							<td style="display: grid; grid-template-columns: 1fr auto; align-items: center;">
								<div class="progress ml-2 mr-2" style="height: 5px;">
									<div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
								</div>
								100%
							</td>
							<td><?=$donation["totalItems"] ?? ""?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<? endif ?>
		</div>
	<?php else: ?>
	<p>
		<i>No donations on record</i>
	</p>
	<? endif ?>
</div>
