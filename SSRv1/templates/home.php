<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $locations */
$this->layout('main', ['title' => 'Home', 'user' => $user]) ?>

<article>
	<h2>Hi</h2>
	<p>This is a temporary home page. Here are some stats.</p>
	<?php if(!empty($locations)): ?>
	<div class="homestats">
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
	if(!empty($serials)): ?>
	<div class="homestats">
		<p>Duplicate serial numbers:</p>
		<table class="home">
			<thead>
			<tr>
				<td>Serial</td>
				<td>Quantity</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($serials as $serial => $count): ?>
				<tr>
					<td><?=$serial?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
	<?php endif ?>
</article>
