<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $locations */
$this->layout('main', ['title' => 'Home', 'user' => $user]) ?>

<article>
	<h2>Hi</h2>
	<p>This is a temporary home page. Here are some stats.</p>
	<?php if(!empty($locations)): ?>
	<p>Available locations, by number of items:</p>
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
	<?php endif ?>
</article>
