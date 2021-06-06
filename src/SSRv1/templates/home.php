<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int[] $locations */
/** @var int[] $recentlyAdded */
$this->layout('main', ['title' => 'Home', 'user' => $user]);
date_default_timezone_set('Europe/Rome');
?>

<h2>Useful key combinations</h2>
<p>In editor mode, select a feature and...</p>
<ul class="list-unstyled">
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>Z</kbd> delete feature</li>
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>U</kbd> CONVERT TO UPPERCASE</li>
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>L</kbd> convert to lowercase</li>
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>Y</kbd> Convert To Title Case</li>
</ul>
<h2>Some statistics</h2>
<div class="row">
<?php if(!empty($locations)): ?>
	<div class="col-md-6">
		<table class="table table-borderless stats">
			<caption>Available locations</caption>
			<thead class="thead-dark">
				<tr>
					<th scope="col">Location</th>
					<th scope="col">Items</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($locations as $row): ?>
				<tr>
					<td style="padding-left:<?=$this->e($row[0])?>rem"><a href="/item/<?=$this->e($row[1])?>?depth=1"><?=$this->e($row[1])?></a></td>
					<td><?=(int) $row[2]?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif;
if(!empty($recentlyAdded)): ?>
	<div class="col-md-6">
		<table class="table table-borderless stats">
			<caption>Last <?= count($recentlyAdded) ?> items added</caption>
			<thead class="thead-dark">
				<tr>
					<th scope="col">Item</th>
					<th scope="col">Added</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($recentlyAdded as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$this->e($code)?>"><?=$this->e($code)?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
</div>
