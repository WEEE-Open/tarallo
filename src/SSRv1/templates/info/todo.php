<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string[][] $todos */
/** @var array[] $missingSmartOrSurfaceScan */
/** @var bool|null $included */
/** @var array[] $toTest */

if(!isset($included) || !$included) {
	$this->layout('main', ['title' => 'TODO', 'user' => $user, 'currentPage' => 'info todo']);
}
$pclass = '';
$divclass = '';
if(isset($included) && $included) {
	$pclass = ' class="my-0"';
	$divclass = ' style="box-shadow: 0 0.2rem 0.4rem #b9b9b9"';
}

$todosWithHumanReadableFeatures = [];
foreach($todos as $feature => $items) {
	$todosWithHumanReadableFeatures[WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableValue(new \WEEEOpen\Tarallo\Feature('todo', $feature))] =
		$items;
}
ksort($todosWithHumanReadableFeatures);
?>
<h2>What's to do?</h2>
<div class="row">
<?php foreach($todosWithHumanReadableFeatures as $feature => $items): ?>
    <?php if(count($items) > 0): ?>
	<div class="stats list col-12 py-2"<?= $divclass ?>>
        <p<?=$pclass?>><?= $feature ?> <small>(<?= count($items) ?>, max 100 shown)</small></p>
        <div>
            <?php foreach($items as $item): ?>
                <a href="/item/<?= $this->e(rawurlencode($item)) ?>"><?= $this->e($item) ?></a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
<?php endforeach ?>
</div>
<div class="row">
<?php if(!empty($missingSmartOrSurfaceScan)): ?>
	<div class="stats list col-12 py-2"<?= $divclass ?>>
		<p<?=$pclass?>>Working HDDs with missing SMART or Surface Scan: use the turbofresa on them! <small>(<?=count($missingSmartOrSurfaceScan)?>, max 200 shown)</small></p>
		<div>
			<?php foreach($missingSmartOrSurfaceScan as $item): ?>
				<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
</div>
<div class="row"<?= $divclass ?>>
	<?php if(!empty($toTest)): ?>
	<?php foreach($toTest as $type => $items): ?>
		<div class="stats list col-md-6 py-2">
			<p<?=$pclass?>><?= $this->e($type) ?> to be tested <small>(<?=count($items)?>, max 100)</small></p>
			<div>
				<?php foreach($items as $item): ?>
					<a href="/item/<?=$this->e(rawurlencode($item))?>"><?=$this->e($item)?></a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endforeach; ?>
	<?php endif ?>
</div>
