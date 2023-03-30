<?php
/** @var bool $showEditButton */
/** @var string $donation|null */

$this->layout('main', ['title' => 'Donations', 'currentPage' => 'donation', 'tooltips' => true]);

?>

<div class="row">
	<div class="itembuttons primary row mx-0 mt-2 justify-content-end w-100">
		<div class="row d-flex m-0 justify-content-between mr-auto">
			<h2 class="col-8 p-0"><?=htmlspecialchars($donation["name"])?></h2>
		</div>
		<?php if ($showEditButton ?? false): ?>
		<a href="/donation/<?=$donation["id"]?>/edit" class="btn btn-outline-primary col-4 col-sm-auto edit mb-2 mr-2">
			<i class="fa fa-pencil-alt"></i>&nbsp;Edit
		</a>
		<? endif ?>
	</div>
	<?php if (isset($donation["location"]) && $donation["location"] != ''): ?>
	<div class="col-12">
		<b>Location:</b> <?=htmlspecialchars($donation["location"])?>
	</div>
	<? endif ?>
	<?php if (isset($donation["date"]) && $donation["date"] != ''): ?>
	<div class="col-12">
		<b>Date:</b> <?=date_format(date_create($donation["date"]),"Y/m/d")?>
	</div>
	<? endif ?>
	<?php if (isset($donation["notes"]) && $donation["notes"] != ''): ?>
	<div class="col-12">
		<b>Notes:</b>
		<?=nl2br(htmlspecialchars($donation["notes"]))?>
	</div>
	<? endif ?>
	<div class="col-12">
		<b>Total items:</b> <?=count($donation["itemsType"])?>
	</div>
	<div class="col-12">
		<b>Progress: </b><span id="progressText"><?=$donation["progress"]?></span>%
		<div class="progress m-2" style="height: 5px;">
			<div class="progress-bar progress-bar-striped bg-info" role="progressbar" id="progressBar" style="width: <?=$donation["progress"]?>%;" aria-valuenow="<?=$donation["progress"]?>" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
		<input type="hidden" id="totalTasks" value="<?=$donation["totalTasks"]?>">
	</div>
	<?php foreach($donation["tasks"] as $type => $tasks): ?>
		<?php $itemsOfType = array_filter($donation["itemsType"], function ($el) use ($type) {return $el === $type;}); 
		if (count($itemsOfType) === 0) continue; ?>
		<div class="col-12">
			<h4><?php $this->insert('productIcon', ['type' => $type, 'color' => 'black']) ?><?=\WEEEOpen\Tarallo\SSRv1\FeaturePrinter::FEATURES_ENUM['type'][$type]?> - <?=count($itemsOfType)?></h4>
			<table class="table table-borderless stats">
				<thead class="thead-dark">
				<tr>
					<th scope="col">Item</th>
					<?php if (is_array($tasks)): ?>
						<?php foreach($tasks as $task): ?>
							<th scope="col"><?=htmlspecialchars($task)?></th>
						<? endforeach ?>
						<th scope="col" class="text-end bg-success">Done</th>
					<?php else: ?>
						<th scope="col" class="text-end bg-success"><?=$tasks?></th>
					<? endif ?>
				</tr>
				</thead>
				<tbody>
				<?php foreach($itemsOfType as $item => $dont_care): ?>
					<tr>
						<td><a href="/item/<?=$item?>"><?=$item?></a></td>
						<?php 
						$allTrue = true;
						if (is_array($donation["tasksProgress"][$item])): ?>

							<?php foreach($donation["tasksProgress"][$item] as $i => $checked): ?>
								<?php $allTrue = $checked && $allTrue; ?>
								<td><div class="form-check">
									<input class="form-check-input" type="checkbox" <?php if ($checked) echo 'checked'; ?> data-donation-id="<?=$donation["id"]?>" data-task-id="<?=$item?>:<?=$i?>">
								</div></td>
							<? endforeach ?>
							<td><div class="form-check">
								<input class="form-check-input" type="checkbox" <?php if ($allTrue) echo 'checked'; ?> data-donation-id="<?=$donation["id"]?>" data-task-id="<?=$item?>:all">
							</div></td>
						<?php else: ?>
							<td><div class="form-check">
								<input class="form-check-input" type="checkbox" <?php if ($donation["tasksProgress"][$item]) echo 'checked'; ?> data-donation-id="<?=$donation["id"]?>" data-task-id="<?=$item?>:-1">
							</div></td>
						<? endif ?>
					</tr>
				<? endforeach ?>
				</tbody>
			</table>
		</div>
	<? endforeach ?>
</div>
<script src="/static/donationTasks.js"></script>