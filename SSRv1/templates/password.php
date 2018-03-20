<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
$this->layout('main', ['title' => 'Change password', 'user' => $user]);
?>
<div id="options">
	<form action="/password" method="POST">
		<label>New password: <input type="password" name="password"></label>
		<label>Confirm: <input type="password" name="confirm"></label>
		<input type="submit" value="Submit">
	</form>
</div>
