<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
/** @var string $self */
$this->layout('main', ['title' => 'Login']) ?>

<nav id="top">
	<nav>
		<form class="message" method="post" action="<?= $self ?>">
			<label>Username: <input name="username" type="text"></label>
			<label>Password: <input name="password" type="password"></label>
			<input type="submit" value="VAI.">
		</form>
	</nav>
</nav>
