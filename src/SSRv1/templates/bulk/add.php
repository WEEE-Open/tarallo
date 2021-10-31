<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array|null $item */
/** @var string|null $error */
$item = $item ?? null;
$this->insert('bulk::menu', ['currentPage' => 'add']);
$this->layout('main', ['title' => 'Bulk add', 'user' => $user, 'currentPage' => 'bulk add', 'container' => true]);
?>

<div class="row">
	<div class="col-12">
		<?php if (isset($error)) : ?>
			<p class="alert alert-danger mt-2" role="alert"><?= $this->e($error) ?></p>
		<?php endif; ?>
		<form action="/bulk/add" method="POST" enctype="multipart/form-data" id="bulk-add" class="mt-3">
			<div class="form-group">
				<label for="bulk-add-id">Identifier (optional)</label>
				<input type="text" class="form-control" name="id" id="bulk-add-id" aria-describedby="bulk-add-id-help">
				<small id="bulk-add-id-help" class="form-text text-muted">To easily find this computer among the imported ones. Any descriptive text is allowed, e.g. "Grey computer on the table"</small>
			</div>
			<div class="form-group">
				<label for="bulk-add-text">Output from peracotta</label>
				<textarea class="form-control" id="bulk-add-text" name="add" rows="15"></textarea>
			</div>
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="overwrite" id="bulk-add-overwrite">
				<label class="form-check-label" for="bulk-add-overwrite">Overwrite if identifier already exists</label>
			</div>
			<input type="submit" value="Submit" class="btn btn-primary mt-3">
		</form>
	</div>
</div>