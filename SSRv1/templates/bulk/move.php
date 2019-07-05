<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string|null $result */
/** @var string|null $error */
/** @var array|null $moved */
$this->layout('main', ['title' => 'Bulk operations', 'user' => $user, 'currentPage' => 'bulk']);
$this->insert('bulk::menu', ['currentPage' => 'move']);
?>
<form action="/bulk/move" method="POST" enctype="multipart/form-data" class="nice" id="bulk-move">
    <h2>Mass move</h2>
    <p>Format is "ITEM:LOCATION" or codes only (using the location below), one per line or separated by
        commas.<button id="bulk-move-button" data-alternate-text="Show text box" class="small">Upload a file</button></p>
    <?php
    if($error !== null || $moved !== null) {
	    if($error === null) {
		    ?><p class="success message">Move completed</p><?php
	    } else {
		    ?><p class="error message"><?= $error ?></p><?php
	    }
    }

    if($moved !== null):
	    $count = count($moved);
	    echo "<p>Moved $count items</p>";

	    ?>
        <div class="tablewrapper">
            <table>
                <thead>
                <tr>
                    <td>Item</td>
                    <td>New location</td>
                </tr>
                </thead>
                <tbody>
			    <?php foreach($moved as $code => $location): ?>
                    <tr>
                        <td><?=$code?></td>
                        <td><?=$location?></td>
                    </tr>
			    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php
    endif;
    ?>
    <label for="bulk-move-items" class="toggle">Items to move:</label>
    <textarea id="bulk-move-items" name="items" rows="5" class="toggle"></textarea>
    <label for="bulk-move-file" class="toggle hidden">Items to move:</label>
    <input id="bulk-move-file" type="file" name="Fitems" class="toggle hidden">
	<label for="bulk-move-location">Location:</label>
    <input id="bulk-move-location" type="text" name="where">
    <input type="submit" value="Move" class="small">
</form>
<script src="/static/bulk.js"></script>
