<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string|null $result */
$this->layout('main', ['title' => 'Move Items', 'user' => $user]);
?>
<form action="\moveAll" method="POST" enctype="multipart/form-data">
    <textarea name="items">Lista degli oggetti</textarea>
    <input type="file" name="Fitems">
    <input type="text" name="where">
    <input type="submit">
</form>