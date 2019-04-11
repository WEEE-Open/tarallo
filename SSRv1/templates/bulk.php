<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string|null $result */
/** @var string|null $error */
/** @var array|null $moved */
$this->layout('main', ['title' => 'Bulk operations', 'user' => $user]);

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
	<div class="statswrapper">
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
<!-- TODO: remove <br>, place label above input -->
<form action="\bulk" method="POST" enctype="multipart/form-data">
	<label>Items to move: <textarea name="items" placeholder="List of items"></textarea></label><br>
	<label>Items to move: <input type="file" name="Fitems"></label><br>
	<label>Location: <input type="text" name="where"></label><br>
	<input type="submit" value="Move">
</form>
