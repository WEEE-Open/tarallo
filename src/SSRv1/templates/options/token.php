<?php

/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\SessionLocal $tokens */
/** @var string|null $newToken */
/** @var string|null $error */
/** @var string|null $deletedTokenDescription */

use WEEEOpen\Tarallo\User;

$this->layout('main', ['title' => 'Options', 'user' => $user, 'currentPage' => 'options', 'container' => true]);
$this->insert('options::menu', ['currentPage' => 'token']);

?>

<?php if ($error !== null) : ?>
<div class="col-12">
	<p class="alert alert-danger" role="alert"><?= $this->e($error) ?></p>
</div>
<?php endif; ?>

<?php if ($newToken !== null) : ?>
<div class="col-12">
	<p class="alert alert-success-old" role="alert">Your new token is: <?=$newToken?></p>
</div>
<?php endif; ?>

<?php if ($deletedTokenDescription !== null) :?>
<div class="col-12">
	<p class="alert alert-danger" role="alert">The token "<?=$deletedTokenDescription?>" has been deleted</p>
</div>
<?php endif; ?>

<div class="col-12">
	<h2>Tokens</h2>
	<table class="table table-borderless table-responsive-lg">
		<caption class="sr-only">List of active tokens</caption>
		<thead class="thead-dark">
		<tr>
			<th>Description</th>
			<th>Creator</th>
			<th>Token</th>
			<th>Permission</th>
			<th>Last access</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($tokens as $token) : ?>
		<tr>
			<?php /** @var \WEEEOpen\Tarallo\SessionLocal $session */
			$session = $token['Session'];
			/** @var DateTime|null $lastAccess */
			$lastAccess = $token['LastAccess']; ?>
			<td><?= $this->e($session->description) ?></td>
			<td><?= $token['Owner']?></td>
			<td class="text-monospace"><?= $this->e($token['Token']) ?>:****************</td>
			<td>
				<?= User::getPermissionLevel($session->level)?></td>
			<td><?= $lastAccess != null ? $this->e($lastAccess->format('Y-m-d')) : 'Error'?></td>
			<td>
				<form method="post">
					<input type="hidden" name="token" value="<?= $this->e($token['Token']) ?>">
					<input type="hidden" name="description" value="<?= $this->e($session->description) ?>">
					<button type="submit" name="delete" value="true" class="btn btn-danger btn-sm">Delete</button>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="col-12">
	<h3>Create a new token</h3>
	<form method="post" action="/options/token">
		<div class="form-group row">
			<label class="col col-form-label" for="description">Description</label>
			<div class="col">
				<input type="text" class="form-control" id="description" name="description">
			</div>
		</div>
		<div class = "form-group row">
			<label class="col col-form-label" for="permission">Permission lvl</label>
			<div class = "col">
				<select class="form-control" id="field" name="field">
					<?php foreach (User::PERMISSION_LEVELS as $level => $name) : ?>
						<option value="<?=$level?>"><?=$name?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="col text-center">
			<button style = "margin-bottom : 25px; margin-top : 25px"
			class="btn btn-primary" type="submit" name="new" value="true">Get token</button>
		</div>
	</form>
</div>
