<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\SessionLocal $tokens */
/** @var string|null $newToken */
/** @var string|null $error */
/** @var array $sessionInfo */
$this->layout(
	'main', [
	'title' => 'Options',
	'user' => $user,
]
) ?>
<?php if($error !== null): ?>
    <p class="error message"><?= $this->e($error) ?></p>
<?php endif; ?>
<?php if($newToken !== null): ?>
    <p class="success message">
        Here
        is
        your
        new
        token: <?= $this->e($newToken) ?></p>
<?php endif; ?>

<table class="xl">
    <thead>
    <tr>
        <!-- owner -->
        <th>
            Description
        </th>
        <!-- cn -->
        <th>
            Token
        </th>
        <!-- uid -->
        <th>
            Permissions
        </th>
        <!-- uid -->
        <th>
            Last
            access
        </th>
        <th>
            Actions
        </th>
    </tr>
    </thead>
    <tbody>
	<?php foreach($tokens

	as $token): ?>
    <tr>
		<?php /** @var $session \WEEEOpen\Tarallo\SessionLocal */
		$session = $token['Session']; ?>
		<?php /** @var $lastAccess DateTime|null */
		$lastAccess = $token['LastAccess']; ?>
        <td><?= $this->e($session->description) ?></td>
        <td><?= $this->e($token['Token']) ?>
            :****************
        </td>
        <td><?= $this->e($session->level) ?></td>
        <td><?= $this->e($lastAccess->format('Y-m-d')) ?></td>
        <td>
            <form method="post">
                <input type="hidden"
                       name="token"
                       value="<?= $this->e($token['Token']) ?>">
                <button type="submit"
                        name="delete"
                        value="true">
                    Delete
                </button>
            </form>
        </td>
		<?php endforeach; ?>
    </tr>
    </tbody>
</table>

<form method="post">
    <label for="description"></label><input
            type="text"
            id="description"
            name="description">
    <button type="submit"
            name="new"
            value="true">
        Get
        token
    </button>
</form>

<p>
    Debug
    info: <?php foreach($sessionInfo as &$s): $s = $s - time(); endforeach;
	echo implode(', ', $sessionInfo) ?></p>
