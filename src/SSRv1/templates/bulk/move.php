<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string|null $result */
/** @var string|null $error */
/** @var array|null $moved */
$this->layout('main', ['title' => 'Bulk operations', 'user' => $user, 'currentPage' => 'bulk move']);
$this->insert('bulk::menu', ['currentPage' => 'move']);
?>

<div class="row">
	<div class="col-12">
	    <?php
			if($error !== null || $moved !== null) {
				if($error === null) {
					$count = count($moved);
					?><p class="alert alert-success" role="alert"><?= "Moved $count items"; ?></p><?php
					?>
						<div class="tablewrapper">
							<table class="table table-borderless stats">
								<thead class="thead-dark">
								<tr>
									<th scope="col">Item</th>
									<th scope="col">New location</th>
								</tr>
								</thead>
								<tbody>
								<?php foreach($moved as $code => $location): ?>
									<tr>
										<td><a href="/item/<?=$this->e($code)?>"><?=$this->e($code)?></a></td>
										<td><a href="/item/<?=$this->e($location)?>"><?=$this->e($location)?></a></td>
									</tr>
								<?php endforeach ?>
								</tbody>
							</table>
						</div>
					<?php
				} else {
					?><p class="alert alert-danger" role="alert"><?= $this->e($error) ?></p><?php
				}
			}
		?>
		<form action="/bulk/move" method="POST" enctype="multipart/form-data" id="bulk-move" class="mt-3">
			<p>Format is "ITEM:LOCATION" or codes only (using the location below), one per line or separated by
				commas. <button id="bulk-move-button" data-alternate-text="Show text box" class="btn btn-secondary btn-sm">Upload a file</button></p>
			<div class="form-group toggle">
				<label for="bulk-move-items" class="toggle">Items to move:</label>
				<textarea id="bulk-move-items" name="items" rows="5" class="form-control"></textarea>
			</div>
			<div class="form-group toggle d-none">
				<label for="bulk-move-file" class="toggle hidden">Items to move:</label>
				<input id="bulk-move-file" type="file" name="Fitems">
			</div>
			<div class="form-group">
				<label for="bulk-move-location">Location:</label>
				<input id="bulk-move-location" type="text" name="where" class="form-control">
			</div>
			<input type="submit" value="Move" class="btn btn-primary mt-3">
		</form>
	</div>
</div>
<div class="row">
	<div class="col-12">
	</div>
</div>
<script src="/static/bulk.js"></script>
