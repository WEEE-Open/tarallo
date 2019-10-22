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

	<section class="history">
		<?php if(empty($history)): ?>
			<p>Nothing to show</p>
		<?php else: ?>
			<table>
				<thead>
				<tr>
                    <td>Time</td>
                    <td>Operator</td>
                    <td>Action</td>
				</tr>
				</thead>
				<tbody>
				<?php date_default_timezone_set('Europe/Rome'); foreach($history as $row): ?>
					<tr>
                        <td><?=date('Y-m-d, H:i', $row['time'])?></td>
                        <td><?= $row['user'] ?></td>
                        <td>
                            <?php switch($row['change']) {
                                case 'C': ?>Created<?php break;
	                            case 'U': ?>Updated features<?php break;
                                case 'D': ?>Deleted item<?php break;
                                case 'M': ?>Moved to <a href="/item/<?= urlencode($this->e($row['other'])) ?>">
	                                <?= $this->e($row['other']) ?></a><?php break;
                                case 'L': ?>Marked as lost<?php break;
                                default: ?>Unknown (<?= $this->e($row['change']) ?>)<?php break;
                            } ?>
                        </td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		<?php endif ?>
        <?php if($tooLong): ?>
            <p>History contains more than <?= count($history) ?> entries. Add the <code>?limit=</code> parameter to the
                URL to see more.</p>
        <?php endif ?>
	</section>
</article>
