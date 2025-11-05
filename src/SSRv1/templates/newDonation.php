<?php
/** @var bool $showDeleteButton|null */
/** @var string $error|null */
/** @var string $name|null */
/** @var string $location|null */
/** @var string $date|null */
/** @var string $notes|null */
/** @var string $itemsList|null */
/** @var string $tasks|null */


$this->layout('main', ['title' => $name, 'currentPage' => 'donation edit', 'tooltips' => true]);
?>

<article class="container">
	<?php if (isset($error)) : ?>
	<div class="alert alert-warning" role="alert">
		<?=$error?>
	</div>
	<?php endif ?>
	<form class="row g-2" action="" enctype="multipart/form-data" method="POST">
		<div class="itembuttons primary row mx-0 mt-2 justify-content-end w-100">
			<div class="row d-flex m-0 justify-content-between mr-auto">
				<h2 class="col-8 p-0">Donation</h2>
			</div>
			<?php if ($showDeleteButton ?? false) : ?>
			<div class="btn btn-outline-danger col-4 col-sm-auto delete mb-2 mr-2">
				<i class="fa fa-trash"></i>&nbsp;Delete
			</div>
			<?php endif ?>
			<input type="submit" id="submit" class="d-none">
			<label for="submit" class="btn btn-success col-4 col-sm-auto save" role="button">
				<i class="fa fa-save"></i>&nbsp;Save
			</label>
		</div>
		<div class="col-12 mb-3">
			<label for="Name">Donation Name: </label>
			<input class="form-control" placeholder="Donation Name" type="text" name="Name" id="Name" value="<?php echo $name ?? ''?>">
		</div>
		<div class="col-12 mb-3">
			<label for="Location">Location: </label>
			<input class="form-control" placeholder="Location" type="text" name="Location" id="Location" value="<?php echo $location ?? ''?>">
		</div>
		<div class="col-12 mb-3">
			<label for="Date">Date: </label>
			<input type="date" name="Date" id="datetime-local" value="<?php echo $date ?? ''?>">
		</div>
		<div class="col-12 mb-3 d-grid">
			<label for="Notes">Notes: </label>
			<textarea name="Notes" id="Notes"><?php echo htmlspecialchars($notes ?? '')?></textarea>
		</div>
		<div class="col-12 mb-3">
			<label>Items list:</label>
			<ul class="list-group item-list-input">
				<input type="hidden" name="ItemsList" value="<?php echo htmlspecialchars($itemsList ?? '')?>">
				<div class="list-group-item input-group mb-3">
					<input type="text" class="form-control" placeholder="Add item" autocomplete="off" data-autocomplete-uri="/v2/autosuggest/code">
					<div class="input-group-append">
						<button class="btn btn-secondary" type="button">Add</button>
					</div>
				</div>
			</ul>
		</div>
		<div class="col-12 mb-3 no-tasks" id="tasksContainer">
			<h5>Tasks:</h5>
			<div class="no-tasks-text"><i>No tasks to show, please add an item before adding tasks</i></div>
			<input type="hidden" name="Tasks" value="<?php echo htmlspecialchars($tasks ?? '')?>">
		</div>
	</form>
</article>
<script src="/static/donation.js"></script>