<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string[][] $todos */
/** @var array[] $missingSmartOrSurfaceScan */
/** @var bool|null $included */

if(!isset($included) || !$included) {
	$this->layout('main', ['title' => 'TODO', 'user' => $user, 'currentPage' => 'info todo']);
}
$pclass = '';
if(isset($included) && $included) {
	$pclass = ' class="my-0"';
}
?>
<h2>What's to do?</h2>
<div class="row">
<?php foreach($todos as $feature => $items): ?>
    <?php if(count($items) > 0): ?>
	<div class="stats list col-12">
        <p<?=$pclass?>><?= WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableValue(new \WEEEOpen\Tarallo\Feature('todo',
				$feature)) ?> <small>(<?= count($items) ?> items, max 100 shown)</small></p>
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
	<div class="stats list col-12">
		<p<?=$pclass?>>Working HDDs with missing SMART or Surface Scan: use the turbofresa on them! <small>(<?=count($missingSmartOrSurfaceScan)?> HDDs, max 200 shown)</small></p>
		<div>
			<?php foreach($missingSmartOrSurfaceScan as $item): ?>
				<a href="/item/<?=$item?>"><?=$item?></a>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>
</div>
