<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var string[][] $history */
/** @var bool $tooLong */
$deletedAt = $item->getDeletedAt();
$this->layout('main', ['title' => $item->getCode() . ' history', 'user' => $user]);

$code_rawurlencoded = rawurlencode($item->getCode());
$code_escaped = $this->e($item->getCode());
?>

<?php $this->insert('breadcrumbs', ['item' => $item]); ?>
<article class="item root<?= $deletedAt === null ? '' : ' deleted' ?>"
		data-code="<?=$code_escaped?>">
	<header>
		<h2 id="code-<?=$code_escaped?>"><?=$code_escaped?></h2>
		<?php if($deletedAt !== null): ?>
            <div class="inline-alert alert-danger" role="alert">❌️️&nbsp;This item has been deleted on <?= $deletedAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
		<?php endif; ?>
	</header>

	<nav class="itembuttons row mx-2 mt-2 justify-content-end">
		<a class="btn btn-outline-primary btn-item col-sm-auto" role="button" href="/item/<?= $code_rawurlencoded ?>">
			<i class="fa fa-search"></i>&nbsp;View
		</a>
	</nav>

	<section class="history row">
		<div class="col-12">
			<?= $this->insert('historyEntries', ['history' => $history, 'tooLong' => $tooLong]) ?>
		</div>
	</section>
</article>
