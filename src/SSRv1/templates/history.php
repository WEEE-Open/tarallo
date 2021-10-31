<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var string[][] $history */
/** @var bool $tooLong */
$deletedAt = $item->getDeletedAt();
$this->layout('main', ['title' => $item->getCode() . ' history', 'user' => $user]);

$code_rawurlencoded = $this->e(rawurlencode($item->getCode()));
$code_escaped = $this->e($item->getCode());
?>

<?php $this->insert('breadcrumbs', ['item' => $item]); ?>
<article class="container item root<?= $deletedAt === null ? '' : ' deleted' ?>"
		data-code="<?=$code_escaped?>">
	<header class="row">
		<h4 class="p-2 col-12 col-md m-0" id="code-<?=$code_escaped?>"><?=$code_escaped?></h4>
		<nav class="p-2 m-0 itembuttons">
			<a class="btn btn-outline-secondary btn-sm btn-item" role="button" href="/item/<?= $code_rawurlencoded ?>">
				<i class="fa fa-search"></i>&nbsp;View
			</a>
		</nav>
	</header>
	<?php if ($deletedAt !== null) : ?>
		<div class="inline-alert w-auto alert-danger" role="alert"><i class="fa fa-trash"></i>&nbsp;This item has been deleted on <?= $deletedAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
	<?php endif; ?>

<!--	<nav class="itembuttons row mx-0 mt-2 justify-content-end">-->
<!--	</nav>-->

	<section class="history row">
		<div class="col-12">
			<?= $this->insert('historyEntries', ['history' => $history, 'tooLong' => $tooLong]) ?>
		</div>
	</section>
</article>
