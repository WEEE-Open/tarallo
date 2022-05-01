<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string|null $error */
/** @var string[] $defaultLocations */
/** @var bool $apcuEnabled */
$this->layout('main', ['title' => 'Options', 'user' => $user, 'currentPage' => 'options', 'container' => true]);
?>
<?php $locationNames = [
	'DefaultCpuLocation' => 'Default location for CPU stats',
	'DefaultRamLocation' => 'Default location for RAM stats',
	'DefaultHddLocation' => 'Default location for HDD stats',
	'DefaultLabLocation' => 'Lab name, for stats',
];
?>

<?php if ($error !== null) : ?>
<div class="col-12">
	<p class="alert alert-danger" role="alert"><?= $this->e($error) ?></p>
</div>
<?php endif; ?>

<div class="col-12">
	<h2>Global options</h2>
	<?php foreach ($locationNames as $location => $name) :?>
	<form method="post">
		<div class="form-group row">
			<label for="inputLocation" class="col col-form-label"><?=$this->e($name)?></label>
			<div class="col">
				<input type="text" id="inputLocation" name="location" class="form-control" value="<?= $this->e($defaultLocations[$location] ?? '') ?>">
			</div>
			<div class="col">
				<button type="submit" class="btn btn-primary" name="default" value="<?=$this->e($location)?>">Save</button>
			</div>
		</div>
	</form>
	<?php endforeach; ?>
</div>