<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
/** @var string $self */
$this->layout('main', ['title' => 'Login']) ?>

<?php $this->push('aftermenu') ?>
<nav>
	<form class="message" method="post" action="<?= $self ?>">
		<label>Username: <input name="username" type="text"></label>
		<label>Password: <input name="password" type="password"></label>
		<input type="submit" value="GO.">
	</form>
	<?php if(isset($failed) && $failed): ?>
	<div class="error message">Wrong username or password</div>
	<?php endif ?>
</nav>
<?php $this->end() ?>
