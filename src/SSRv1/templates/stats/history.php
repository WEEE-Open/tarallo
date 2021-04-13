<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array[] $lastItemEntries */
/** @var array[] $lastProductEntries */

$this->layout('main', ['title' => 'Stats: history', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'history']);

$now = new DateTime();
?>

<div class="row">
	<div class="col">
		<table class="table table-borderless stats">
			<caption>Last changes on items</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Items</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($lastItemEntries as $line): ?>
				<tr>
					<?php $sentence = '';
					switch($line['Change']){
						case 'C':
							$sentence = ' created';
							break;
						case 'U':
							$sentence = ' updated';
							break;
						case 'M':
							$sentence = ' moved to <a href="/item/' . $line['Other'] . '">' . $line['Other'] . "</a>";
							break;
						case 'L':
							$sentence = ' lost';
							break;
						case 'D':
							$sentence = ' deleted';
							break;
						default:
							$sentence = ' unknown operation ' . $line['Change'] . ($line['Other'] ? ' with ' . $line['Other'] : '');
					}?>
					<?php $date = new DateTime($line['Time']);

					$time = $date->diff($now)->days > 0 ? $date->format('\a\t H:i \o\n F j') : $date->format('\a\t H:i');
					?>
					<td class="align-middle"><a href="/item/<?=$line['Code']?>"><?=$line['Code']?></a><?=$sentence?>
						<small class="text-muted">by <?= $this->e($line['User']) ?> <?= $this->e($time)?></small>
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>	<div class="col">
		<table class="table table-borderless stats">
			<caption>Last changes on products</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Products</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($lastProductEntries as $line): ?>
				<tr>
					<?php $sentence = '';
					switch($line['Change']){
						case 'C':
							$sentence = ' created';
							break;
						case 'U':
							$sentence = ' updated';
							break;
						case 'M':
							$sentence = ' moved to <a href="/product/' . $line['Other'] . '">' . $line['Other'] . "</a>";
							break;
						case 'L':
							$sentence = ' lost';
							break;
						case 'D':
							$sentence = ' deleted';
							break;
						default:
							$sentence = ' unknown operation ' . $line['Change'] . ($line['Other'] ? ' with ' . $line['Other'] : '');
					}?>
					<?php $date = new DateTime($line['Time']);

					$time = $date->diff($now)->days > 0 ? $date->format('\a\t H:i \o\n F j') : $date->format('\a\t H:i');
					?>
					<td class="align-middle">
						<a href="/product/<?=$line['Brand']?>/<?=$line['Model']?>/<?=$line['Variant']?>">
							<?=$line['Brand'] . " " . $line['Model']?> <small><?=$line['Variant']?></small>
						</a><?=$sentence?>
						<small class="text-muted">by <?= $this->e($line['User']) ?> <?= $this->e($time)?></small>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>
