<?php
/** @var string[][] $history */
/** @var bool $tooLong */
?>
<?php if (empty($history)) : ?>
	<p>Nothing to show</p>
<?php else : ?>
	<table class="table table-sm table-striped table-borderless">
		<thead class="thead-dark">
		<tr>
			<th scope="col">Time</th>
			<th scope="col">Operator</th>
			<th scope="col">Action</th>
		</tr>
		</thead>
		<tbody>
		<?php date_default_timezone_set('Europe/Rome'); foreach ($history as $row) : ?>
			<tr>
				<td><?=date('Y-m-d, H:i', $row['time'])?></td>
				<td><?= $row['user'] ?></td>
				<td>
					<?php switch ($row['change']) {
						case 'C':
							?>Created<?php
							break;
						case 'R':
							?>Renamed from <?= $this->e($row['other']) ?><?php
							break;
						case 'U':
							?>Updated features<?php
							break;
						case 'D':
							?>Deleted item<?php
							break;
						case 'M':
							?>Moved to <a href="/item/<?= urlencode($this->e($row['other'])) ?>">
							<?= $this->e($row['other']) ?></a><?php
							break;
						case 'L':
							?>Marked as lost<?php
							break;
						default:
							?>Unknown (<?= $this->e($row['change']) ?>)<?php
							break;
					} ?>
				</td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
<?php if ($tooLong) : ?>
	<p>History contains more than <?= count($history) ?> entries. Add the <code>?limit=</code> parameter to the
		URL to see more.</p>
<?php endif ?>
