<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string|null $result */
/** @var string|null $error */
/** @var array|null $moved */
$this->layout('main', ['title' => 'Bulk operations', 'user' => $user, 'currentPage' => 'bulk']);
$this->insert('bulk::menu', ['currentPage' => 'add']);
?>
<form action="\bulk\add" method="POST" enctype="multipart/form-data" class="nice" id="bulk-add">
    <h2>Add a calcolatore</h2>
    <p>Paste output from peracotta</p>
    <?php
    if($error !== null || $moved !== null) {
	    if($error === null) {
		    ?><p class="success message">Move completed</p><?php
	    } else {
		    ?><p class="error message"><?= $error ?></p><?php
	    }
    }
    ?>
    <label for="bulk-add-text">Things</label>
    <textarea id="bulk-add-text" name="add" rows="15"></textarea>
    <input type="submit" value="Move" class="small">
</form>
