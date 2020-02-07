<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var string[][] $history */
/** @var bool $tooLong */
$deletedAt = $item->getDeletedAt();
$this->layout('main', ['title' => $item->getCode() . ' history', 'user' => $user, 'itembuttons' => true]);
?>

<?php $this->insert('breadcrumbs', ['item' => $item]); ?>
<article class="item root<?= $deletedAt === null ? '' : ' deleted' ?>"
		data-code="<?=$this->e($item->getCode())?>">
	<header>
		<h2 id="code-<?=$this->e($item->getCode())?>"><?=$this->e($item->getCode())?></h2>
		<?php if($deletedAt !== null): ?>
            <div class="error message">❌️️&nbsp;This item has been deleted on <?= $deletedAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
		<?php endif; ?>
	</header>

	<nav class="itembuttons" data-for-item="<?=$this->e($item->getCode())?>">
		<button class="view">🔍&nbsp;View</button>
	</nav>

	<section class="history row">
		<?= $this->insert('historyEntries', ['history' => $history, 'tooLong' => $tooLong]) ?>
	</section>
</article>
