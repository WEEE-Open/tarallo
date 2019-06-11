<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string[][] $todos */
$this->layout('main', ['title' => 'Stats: TODOs', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'todo']);
?>
<div class="statswrapperwrapper">
<?php foreach($todos as $feature => $items): ?>
    <?php if(count($items) > 0): ?>
    <div class="tablewrapper large">
        <p><?= WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableValue(new \WEEEOpen\Tarallo\Server\Feature('todo',
                $feature)) ?> (max 100 items):</p>
        <div>
            <?php foreach($items as $item): ?>
                <a href="/item/<?= rawurlencode($item) ?>"><?= $this->e($item) ?></a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
<?php endforeach ?>
</div>
