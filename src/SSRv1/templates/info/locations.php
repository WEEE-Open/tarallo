<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $locations */

$this->layout('main', ['title' => 'Locations', 'user' => $user, 'currentPage' => 'info locations']);
//date_default_timezone_set('Europe/Rome');
//$first = true;
?>

<h2>All locations</h2>
<div class="row">
	<?php if(!empty($locations)): ?>
		<div class="col-12">
			<table class="table table-borderless stats">
				<caption>Available locations</caption>
				<thead class="thead-dark">
				<tr>
					<th scope="col">Location</th>
					<th scope="col">Description</th>
					<th scope="col">Items</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach($locations as $row): ?>
				<?php
					$borderClass = '';
					if((int) $row[0] === 0) {
//						if($first) {
//							$first = false;
//						} else {
							$borderClass = 'class="rootitem"';
//						}
					}
					$color = isset($row[4]) ? '<i class="fa fa-square ml-1" title="' . $this->e(\WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableValue(new \WEEEOpen\Tarallo\Feature('color', $row[4]))) . '" style="color:' . $this->colorToHtml($row[4]) . '"></i>' : '';

				?>
					<tr <?= $borderClass ?>>
						<td style="padding-left:<?=(int) $row[0]?>rem;"><a href="/item/<?=$this->e($row[1])?>?depth=1"><?=$this->e($row[1])?></a><?=$color?></td>
						<td><?= $this->e($row[3]) ?></td>
						<td><?=(int) $row[2]?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
