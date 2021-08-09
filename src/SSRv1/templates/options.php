<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\SessionLocal $tokens */
/** @var string|null $newToken */
/** @var string|null $error */
/** @var string[] $defaultLocations */
$this->layout('main', ['title' => 'Options', 'user' => $user, 'currentPage' => 'options', 'container' => true]);
?>
<?php $locationNames = [
	'DefaultRamLocation' => 'Default location for RAM stats',
	'DefaultCpuLocation' => 'Default location for CPU stats',
	'DefaultLabLocation' => 'Lab name, for stats',
];
?>

<?php if($error !== null): ?>
<div class="col-12">
	<p class="alert alert-danger" role="alert"><?= $this->e($error) ?></p>
</div>
<?php endif; ?>
<?php if($newToken !== null): ?>
<div class="col-12">
	<p class="alert alert-success" role="alert">Here is your new token: <?= $this->e($newToken) ?></p>
</div>
<?php endif; ?>

<div class="col-12">
	<h2>Tokens</h2>
	<table class="table table-borderless table-responsive-lg">
		<caption class="sr-only">List of active tokens</caption>
		<thead class="thead-dark">
		<tr>
			<th>Description</th>
			<th>Token</th>
			<th>Permissions</th>
			<th>Last access</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach($tokens as $token): ?>
		<tr>
			<?php /** @var $session \WEEEOpen\Tarallo\SessionLocal */
			$session = $token['Session']; ?>
			<?php /** @var $lastAccess DateTime|null */
			$lastAccess = $token['LastAccess']; ?>
			<td><?= $this->e($session->description) ?></td>
			<td class="text-monospace"><?= $this->e($token['Token']) ?>:****************</td>
			<td><?= $this->e($session->level) ?></td>
			<td><?= $this->e($lastAccess->format('Y-m-d')) ?></td>
			<td>
				<form method="post">
					<input type="hidden" name="token" value="<?= $this->e($token['Token']) ?>">
					<button type="submit" name="delete" value="true" class="btn btn-danger btn-sm">Delete</button>
				</form>
			</td>
			<?php endforeach; ?>
		</tr>
		</tbody>
	</table>
</div>

<div class="col-12">
	<h3>Create a new token</h3>
	<form method="post">
		<div class="form-group row">
			<label class="col col-form-label" for="description">Description</label>
			<div class="col">
				<input type="text" class="form-control" id="description" name="description">
			</div>
			<div class="col">
				<button class="btn btn-primary" type="submit" name="new" value="true">Get token</button>
			</div>
	</form>
</div>
<?php if($user->getLevel() === \WEEEOpen\Tarallo\User::AUTH_LEVEL_ADMIN): ?>
<h2>Global options</h2>
<?php foreach($locationNames as $location => $name):?>
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
<?php endif; ?>

