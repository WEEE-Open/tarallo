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
	<p>All stats refer to <em>every possible location</em></p>
<?php else: ?>
	<p>All stats refer to <em><?=$location?></em> only</p>
<?php endif; ?>
<?php if($allowDateSelection): ?>
<div class="statsheader">
	<form action="" method="GET">
		<label>Start date<input type="date" name="from"></label>
		<?php if($location !== null && $locationSet): ?><input type="hidden" name="where" value="<?= $this->e($location) ?>"><?php endif; ?>
		<input type="submit" value="Filter">
	</form>
</div>
<?php endif; ?>
<div class="statsheader">
	<form action="" method="GET">
		<label>Location<input type="text" name="where" placeholder="<?= $this->e($location ?? '') ?>"></label>
		<?php if($allowDateSelection): if($startDate !== null && $startDateSet): ?><input type="hidden" name="from" value="<?= $startDate->format('Y-m-s') ?>"><?php endif; endif; ?>
		<input type="submit" value="Filter">
	</form>
</div>
