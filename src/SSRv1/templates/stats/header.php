<?php
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var bool $allowDateSelection */
if(!isset($allowDateSelection)) {
	$allowDateSelection = true;
}
if($location === null): ?>
	<p class="stats text">All stats refer to <em>every possible location</em></p>
<?php else: ?>
	<p class="stats text">All stats refer to <em><?=$location?></em> only</p>
<?php endif; ?>

<?php if($allowDateSelection): ?>
<form action="" method="GET">
	<div class="form-group row">
		<label for="statsDate" class="col-sm-2 col-lg-1 col-form-label">Start&nbsp;date</label>
		<div class="col-8 col-sm-5 col-md-4">
			<input type="date" name="from" class="form-control" id="statsDate">
		</div>
		<div class="col-4 col-sm">
			<input class="btn btn-secondary" type="submit" value="Filter">
		</div>
	</div>
	<?php if($location !== null && $locationSet): ?><input type="hidden" name="where" value="<?= $this->e($location) ?>"><?php endif; ?>
</form>
<?php endif; ?>
<form action="" method="GET">
	<div class="form-group row">
		<label for="statsWhere" class="col-sm-2 col-lg-1 col-form-label">Location</label>
		<div class="col-8 col-sm-5 col-md-4">
			<input type="text" name="where" class="form-control" id="statsWhere" placeholder="<?= $this->e($location ?? '') ?>">
		</div>
		<div class="col-4 col-sm">
			<input class="btn btn-secondary" type="submit" value="Filter">
		</div>
	</div>
	<?php if($allowDateSelection): if($startDate !== null && $startDateSet): ?><input type="hidden" name="from" value="<?= $startDate->format('Y-m-s') ?>"><?php endif; endif; ?>
</form>
