<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var string|null $add */
/** @var string|null $edit */
/** @var bool $recursion */
$recursion = $recursion ?? false;
$features = $item->getFeatures();
$lostAt = $item->getLostAt();
$deletedAt = $item->getDeletedAt();

// Too much logic in a template, blah bla blah... this templates renders an Item in every respect,
// what would I gain by moving this logic outside and passing $works as a parameter? More code, scattering
// templates-related stuff around (Adapter and other classes don't care if items are working or broken!),
// duplicating information, increasing probability of introducing bugs?
$working = '';
if(isset($features['working'])) {
	$working = ' working ' . $this->e($features['working']->value);
}

$containsMore = count($item->getContent()) > 0;

// Until proven guilty, er, true
$adding = false;
$editing = false;
$target = false;

$nextItemParameters = ['recursion' => true];
if(isset($edit)) {
	$nextItemParameters['edit'] = $edit;
	$editing = true;
	if(strtolower($edit) === strtolower($item->getCode())) {
		$target = true;
	} else {
		$target = false;
	}
} else if(isset($add)) {
	$nextItemParameters['add'] = $add;
	$adding = true;
	if(strtolower($add) === strtolower($item->getCode())) {
		$target = true;
	} else {
		$target = false;
	}
}

$summary = \WEEEOpen\Tarallo\SSRv1\Summary\Summary::peel($item);

?>

<?php if(!$recursion): $this->insert('breadcrumbs', ['item' => $item]); endif; ?>
<article class="item<?=$recursion ? '' : ' root'?><?=$working?><?=$editing && $target ? ' head editing' : ''?><?= $deletedAt === null ? '' : ' deleted' ?>"
		data-code="<?=$this->e($item->getCode())?>">
	<header>
		<h2 id="code-<?=$this->e($item->getCode())?>"><?=$this->e($item->getCode())?></h2>
		<?php if($deletedAt === null): ?>
            <?php if($item->getFeature('restrictions') !== null): ?>
                <div class="info message">ℹ️&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature
                    ($item->getFeature('restrictions'), $lang ?? 'en'))->pvalue; ?></div>
            <?php endif; ?>
            <?php if($item->getFeature('check') !== null): ?>
                <div class="warning message">⚠️️&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature
                    ($item->getFeature('check'), $lang ?? 'en'))->pvalue; ?></div>
            <?php endif; ?>
            <?php if($item->getFeature('todo') !== null): ?>
                <div class="info message">➡️️️&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature
                    ($item->getFeature('todo'), $lang ?? 'en'))->pvalue; ?></div>
            <?php endif; ?>
		<?php else: ?>
			<div class="error message">❌️️&nbsp;This item has been deleted on <?= $deletedAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
		<?php endif; ?>
		<?php if($lostAt !== null): ?>
            <div class="serious message">🏷️️️&nbsp;This item has been lost on <?= $lostAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
		<?php endif; ?>
	</header>

	<nav class="itembuttons" data-for-item="<?=$this->e($item->getCode())?>">
		<?php if($editing && $target): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><?php if(!$containsMore): ?><button class="lost">🏷&nbsp;Lost</button><button class="delete">❌&nbsp;Delete</button><?php endif ?>
		<?php elseif(!$adding && !$editing): ?>
			<?php if($deletedAt === null): ?><button class="addinside">📄&nbsp;Add</button><button class="edit">🛠️&nbsp;Edit</button><button class="clone">🔲&nbsp;Copy</button><button class="move">📍&nbsp;Move</button><?php endif ?><button class="history">📖&nbsp;History</button>
		<?php endif ?>
	</nav>
	<?php if($summary !== null && (!$editing || !$target)): ?>
        <section class="summary <?=$working?>">
            <span><?= $summary ?></span>
        </section>
    <?php endif; ?>

	<?php if($editing && $target): ?>
		<section class="own features editing">
			<?php
			$this->insert('featuresEdit', ['features' => $item->getFeatures()]);
			?>
		</section>

		<section class="addfeatures">
			<label>Feature:
				<select class="allfeatures">
				</select></label><button>Add</button>
		</section>

		<!--<section class="product features">
			$this->insert('features', ['features' => $product === null ? [] : $product->getFeatures()])
		</section>-->
	<?php else: ?>
		<section class="features">
			<?php $this->insert('features', ['features' => $features, 'product' => $item->getProduct() === null ? [] : $item->getProduct()->getFeatures()]) ?>
		</section>
	<?php endif ?>

	<section class="subitems">
		<?php
		if($adding && $target) {
			$empty = new \WEEEOpen\Tarallo\ItemIncomplete(null);
			$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('type'));
			//$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('working'));
			$this->insert('newItem', ['recursion' => true, 'innerrecursion' => false, 'base' => $empty]);
		}

		$subitems = $item->getContent();
		foreach($subitems as $subitem) {
			$this->insert('item', array_merge($nextItemParameters, ['item' => $subitem]));
		}
		?>
	</section>
</article>
<?php if($editing && $target): /* newItem activates the editor on its own */ ?>
	<script>const activate = true;</script>
	<?php $this->insert('editor');
endif;
?>
