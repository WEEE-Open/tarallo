<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $leastRecent */
/** @var int[] $mostRecent */
/** @var int[] $byOwner */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
/** @var string[] $ready */
$this->layout('main', ['title' => 'Stats', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'cases']);
date_default_timezone_set('Europe/Rome');
$this->insert('stats::header', ['location' => $location, 'locationSet' => $locationSet, 'startDate' => $startDate, 'startDateSet' => $startDateSet]);
?>
<div class="statswrapperwrapper">
<?php if(!empty($ready)): ?>
	<div class="statswrapper large">
		<p>Ready computers:</p>
		<div>
			<?php foreach($ready as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
<?php if(!empty($leastRecent)): ?>
	<div class="statswrapper">
		<p>30 computers where no work has been done in a long time:</p>
		<table>
			<thead>
			    <tr>
				    <td>Case</td>
				    <td>Last action</td>
			    </tr>
			</thead>
			<tbody>
			<?php foreach($leastRecent as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($mostRecent)): ?>
	<div class="statswrapper">
		<p>30 computers where work has been done recently:</p>
		<table>
			<thead>
			<tr>
				<td>Case</td>
				<td>Last action</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($mostRecent as $code => $time): ?>
				<tr>
					<td><a href="/item/<?=$code?>"><?=$code?></a></td>
					<td><?=date('Y-m-d, H:i', $time)?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($byOwner)): ?>
	<div class="statswrapper">
		<p>Computers by owner<?php if($startDate !== null):?> (acquired after <?=$startDate->format('Y-m-d')?>)<?php endif; ?>:</p>
		<table>
			<thead>
			<tr>
				<td>Owner</td>
				<td>Count</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byOwner as $owner => $count): ?>
				<tr>
					<td><?=$owner?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($byMobo)): ?>
	<div class="statswrapper">
		<p>Cases by motherboard form factor<?php if($startDate !== null):?> (acquired after <?=$startDate->format('Y-m-d')?>)<?php endif; ?>:</p>
		<table>
			<thead>
			<tr>
				<td>Form factor</td>
				<td>Count</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byMobo as $type => $count): ?>
				<tr>
					<td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('motherboard-form-factor', $type), $lang ?? 'en')?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
<?php if(!empty($byDate)): ?>
	<div class="statswrapper">
		<p>Owner by date:</p>
		<table>
			<thead>
			<tr>
				<td>Owner</td>
				<td>Count</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($byDate as $owner => $count): ?>
				<tr>
					<td><?=$owner?></td>
					<td><?=$count?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php endif ?>
</div>
