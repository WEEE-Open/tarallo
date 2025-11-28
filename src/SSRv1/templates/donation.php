<?php
/** @var bool $old */
/** @var bool $showEditButton */
/** @var string[]|null $donation */

$this->layout('main', ['title' => $donation["name"], 'currentPage' => 'donation', 'tooltips' => true]);
?>

<div class="row">
	<?php if ($old) : ?>
		<div class="itembuttons primary row mx-0 mt-2 justify-content-end w-100">
			<div class="row d-flex m-0 ml-3 mr-auto justify-content-between">
				<h2 class="col-8 p-0 text-nowrap"><?=htmlspecialchars($donation["name"])?></h2>
			</div>
			<a href="/donation/<?=$donation["id"]?>/download" class="btn btn-outline-primary col-4 col-sm-auto edit mb-2 mr-2" download>
				<i class="fa fa-download"></i>&nbsp;Download as excel
			</a>
			<?php if ($showEditButton ?? false) : ?>
				<?php if ($donation["isCompleted"]) : ?>
			<a href="/donation/<?=$donation["id"]?>/uncomplete" class="btn btn-outline-warning col-4 col-sm-auto complete mb-2 mr-2">
				<i class="fa fa-check"></i>&nbsp;Unmark as done
			</a>
				<?php else : ?>
			<a href="/bulk/move?items=<?=htmlspecialchars(implode(',', array_keys($donation["tasksProgress"])))?>" class="btn btn-outline-primary col-4 col-sm-auto edit mb-2 mr-2">
				<i class="fas fa-dolly"></i>&nbsp;Move all items
			</a>
			<a href="/donation/<?=$donation["id"]?>/complete" class="btn btn-outline-warning col-4 col-sm-auto complete mb-2 mr-2">
				<i class="fa fa-check"></i>&nbsp;Mark as done
			</a>
			<a href="/donation/<?=$donation["id"]?>/edit" class="btn btn-outline-primary col-4 col-sm-auto edit mb-2 mr-2">
				<i class="fa fa-edit"></i>&nbsp;Edit
			</a>
				<?php endif ?>
			<?php endif ?>
		</div>
		<?php if (isset($donation["location"]) && $donation["location"] != '') : ?>
		<div class="col-12">
			<b>Location:</b> <?=htmlspecialchars($donation["location"])?>
		</div>
		<?php endif ?>
		<?php if (isset($donation["date"])) : ?>
		<div class="col-12">
			<b>Date:</b> <?=$donation["date"]?>
		</div>
		<?php endif ?>
		<?php if (isset($donation["notes"]) && $donation["notes"] != '') : ?>
		<div class="col-12">
			<b>Notes:</b>
			<?=nl2br(htmlspecialchars($donation["notes"]))?>
		</div>
		<?php endif ?>
		<div class="col-12">
			<b>Total items:</b> <?=count($donation["itemsType"])?>
		</div>
		<?php if ($donation["isCompleted"]) : ?>
		<div class="col-12">
			<b>Progress: </b>100%
			<div class="progress m-2" style="height: 5px;">
				<div class="progress-bar progress-bar-striped bg-success" role="progressbar" id="progressBar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
		</div>
		<div class="col-12">
			<h4>Donated Items</h4>
			<?php
			$grouped = [];
			foreach ($donation["itemsType"] as $item => $type) {
				if (is_array($grouped[$type] ?? null)) {
					$grouped[$type][] = $item;
				} else {
					$grouped[$type] = [$item];
				}
			}
			if (count($donation["itemsType"]) !== 0) :
				foreach ($grouped as $type => $items) : ?>
			<h4><?php $this->insert('productIcon', ['type' => $type, 'color' => 'black']) ?><?=\WEEEOpen\Tarallo\SSRv1\FeaturePrinter::FEATURES_ENUM['type'][$type] ?? 'Other'?> - <?=count($items)?></h4>
			<ul>
					<?php foreach ($items as $item) : ?>
					<li><a href="/item/<?=$item?>"><?=$item?></a></li>
					<?php endforeach ?>
			</ul>
				<?php endforeach ?>
			<?php else : ?>
			<i>No items in donation</i>
			<?php endif ?>
		</div>
		<?php else : ?>
		<div class="col-12">
			<b>Progress: </b><span id="progressText"><?=$donation["progress"]?></span>%
			<div class="progress m-2" style="height: 5px;">
				<div class="progress-bar progress-bar-striped bg-info" role="progressbar" id="progressBar" style="width: <?=$donation["progress"]?>%;" aria-valuenow="<?=$donation["progress"]?>" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
			<input type="hidden" id="totalTasks" value="<?=$donation["totalTasks"]?>">
		</div>
			<?php foreach ($donation["tasks"] as $type => $tasks) : ?>
				<?php $type = $type ?? 'other';
				$itemsOfType = array_filter($donation["itemsType"], function ($el) use ($type) {
					return ($el ?? 'other') === $type;
				});
				if (count($itemsOfType) === 0) {
					continue;
				} ?>
			<div class="col-12">
				<h4><?php $this->insert('productIcon', ['type' => $type, 'color' => 'black']) ?><?=\WEEEOpen\Tarallo\SSRv1\FeaturePrinter::FEATURES_ENUM['type'][$type] ?? 'Other' ?> - <?=count($itemsOfType)?></h4>
				<table class="table table-borderless stats">
					<thead class="thead-dark">
					<tr>
						<th scope="col">Item</th>
						<?php if (is_array($tasks)) : ?>
							<?php foreach ($tasks as $task) : ?>
								<th scope="col"><?=htmlspecialchars($task)?></th>
							<?php endforeach ?>
							<th scope="col" class="text-end bg-success">Done</th>
						<?php else : ?>
							<th scope="col" class="text-end bg-success"><?=$tasks?></th>
						<?php endif ?>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($itemsOfType as $item => $dont_care) : ?>
						<tr>
							<td><a href="/item/<?=$item?>"><?=$item?></a></td>
							<?php
							$allTrue = true;
							if (is_array($donation["tasksProgress"][$item])) : ?>
								<?php foreach ($donation["tasksProgress"][$item] as $i => $checked) : ?>
									<?php $allTrue = $checked && $allTrue; ?>
									<td><div class="form-check">
										<input class="form-check-input" type="checkbox" <?php if ($checked) {
											echo 'checked';
																						} ?> data-donation-id="<?=$donation["id"]?>" data-task-id="<?=$item?>:<?=$i?>">
									</div></td>
								<?php endforeach ?>
								<td><div class="form-check">
									<input class="form-check-input" type="checkbox" <?php if ($allTrue) {
										echo 'checked';
																					} ?> data-donation-id="<?=$donation["id"]?>" data-task-id="<?=$item?>:all">
								</div></td>
							<?php else : ?>
								<td><div class="form-check">
									<input class="form-check-input" type="checkbox" <?php if ($donation["tasksProgress"][$item]) {
										echo 'checked';
																					} ?> data-donation-id="<?=$donation["id"]?>" data-task-id="<?=$item?>:-1">
								</div></td>
							<?php endif ?>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</div>
			<?php endforeach ?>
		<?php endif ?>
	<?php else : ?>
		<div id="app"></div>
		<script src="/script-ng/donation.js"></script>
		<script>
			initDonation(<?=json_encode($donation)?>);
		</script>
	<?php endif; ?>
</div>
<?php if (!$donation["isCompleted"] && $old) :
	?> <script src="/static/donationTasks.js"></script> <?php
endif ?>