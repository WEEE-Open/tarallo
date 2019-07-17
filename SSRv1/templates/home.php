<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $locations */
/** @var int[] $recentlyAdded */
$this->layout('main', ['title' => 'Home', 'user' => $user]) ?>

<article>
	<h2>Useful key combinations</h2>
	<p>In editor mode, select a feature and...</p>
    <ul>
        <li>Ctrl + Alt + Z: delete feature</li>
        <li>Ctrl + Alt + U: CONVERT TO UPPERCASE</li>
        <li>Ctrl + Alt + L: convert to lowercase</li>
        <li>Ctrl + Alt + Y: Convert To Title Case</li>
    </ul>
    <h2>Random stats</h2>
	<div class="statswrapperwrapper">
	<?php if(!empty($locations)): ?>
		<div class="tablewrapper">
			<p>Available locations:</p>
			<table>
				<thead>
				<tr>
					<td>Location</td>
					<td>Items</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach($locations as $code => $count): ?>
					<tr>
						<td><?=$code?></td>
						<td><?=$count?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif;
	if(!empty($recentlyAdded)): ?>
		<div class="tablewrapper">
			<p>Last <?= count($recentlyAdded) ?> items added:</p>
			<table>
				<thead>
				<tr>
					<td>Item</td>
					<td>Added</td>
				</tr>
				</thead>
				<tbody>
				<?php date_default_timezone_set('Europe/Rome'); foreach($recentlyAdded as $code => $time): ?>
					<tr>
						<td><a href="/item/<?=$code?>"><?=$code?></a></td>
						<td><?=date('Y-m-d, H:i', $time)?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
	</div>
</article>
