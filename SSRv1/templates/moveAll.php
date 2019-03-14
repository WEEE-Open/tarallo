<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string|null $result */
$this->layout('main', ['title' => 'Move Items', 'user' => $user]);
?>
<div id="moveAll">
    <form action="/moveAll" method="POST">
        <textarea>Lista degli oggetti</textarea>
        <input type="file" name="Items">
        <input type="submit">
    </form>
</div>
