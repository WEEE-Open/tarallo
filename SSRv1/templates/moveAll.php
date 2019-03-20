<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string|null $result */
$this->layout('main', ['title' => 'Move Items', 'user' => $user]);
?>
<form action="\moveAll" method="POST">
    <textarea name="items">Lista degli oggetti</textarea>
    <input type="file" name="Fitems">
    <input type="submit">
    <input type="text" name="where">

</form>
