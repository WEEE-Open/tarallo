<?php
/** @var User $user */
/** @var \WEEEOpen\Tarallo\SessionLocal $tokens */
/** @var string|null $newToken */
/** @var string|null $error */
/** @var string[] $defaultLocations */
use WEEEOpen\Tarallo\User;
$this->layout('main', ['title' => 'Options', 'user' => $user, 'currentPage' => 'options']);
?>
<?php $locationNames = ['DefaultRam', 'test2', 'test3', 'test4'];?>

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
			<label class="col-sm-2 col-lg-1 col-form-label mb-1" for="description">Description</label>
			<div class="col-sm-10 col-lg-9">
				<input type="text" class="form-control mb-2" id="description" name="description">
			</div>
			<div class="col-lg-2">
				<button class="btn btn-primary mb-2 w-lg-100" type="submit" name="new" value="true">Get token</button>
			</div>
	</form>
</div>
<?php if($user->getLevel() === User::AUTH_LEVEL_ADMIN): ?>
<h3>Edit default locations</h3>
<?php foreach($locationNames as $location):?>
<form method="post">
	<div class="form-group row">
			<label for="inputLocation" class="col-sm-2 col-lg-1 col-form-label mb-1"><?=$this->e($location)?></label>
		<div class="col">
			<input type="text" id="inputLocation" name="location" class="form-control mb-2" placeholder=<?= $defaultLocations[$location] ?? '' ?>>
		</div>
		<div class="col-lg-2">
			<button type="submit" class="btn btn-primary mb-2 w-lg-100" name="default" value=<?=$location?>>Save</button>
		</div>
	</div>
</form>
<?php endforeach; ?>
<?php endif; ?>

