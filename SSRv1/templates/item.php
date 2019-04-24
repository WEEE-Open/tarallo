<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
/** @var string|null $add */
/** @var string|null $edit */
/** @var \DateTime|null $deletedAt */
/** @var bool $recursion */
$recursion = $recursion ?? false;
$features = $item->getCombinedFeatures();
$product = $item->getProduct();

// Too much logic in a template, blah bla blah... this templates renders an Item in every respect,
// what would I gain by moving this logic outside and passing $works as a parameter? More code, scattering
// templates-related stuff around (Adapter and other classes don't care if items are working or broken!),
// duplicating information, increasing probability of introducing bugs?
$working = '';
if(isset($features['working'])) {
	$value = $features['working']->value;
	switch($value) {
		case 'yes':
		case 'no':
		case 'maybe':
			$working = " working $value";
			break;
	}
	unset($value);
}

$containsMore = count($item->getContents()) > 0;

// Until proven guilty, er, true
$adding = false;
$editing = false;
$target = false;

$nextItemParameters = ['recursion' => true, 'deletedAt' => null];
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
?>

<?php if(!$recursion): $this->insert('breadcrumbs', ['item' => $item]); endif; ?>
<article class="item<?=$recursion ? '' : ' root'?><?=$working?><?=$editing && $target ? ' head editing' : ''?><?= $deletedAt === null ? '' : ' deleted' ?>"
		data-code="<?=$this->e($item->getCode())?>">
	<header>
		<h2 id="code-<?=$this->e($item->getCode())?>"><?=$this->e($item->getCode())?></h2>
		<?php $noticeFeature = $item->getFeature('restrictions'); if($noticeFeature !== null): ?>
			<div class="info message">ℹ️&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature($noticeFeature, $lang ?? 'en'))->value; ?></div>
		<?php unset($noticeFeature); endif;
		$noticeFeature = $item->getFeature('check'); if($noticeFeature !== null): ?>
			<div class="warning message">⚠️️&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature($noticeFeature, $lang ?? 'en'))->value; ?></div>
		<?php unset($noticeFeature); endif;
		if($deletedAt !== null): ?>
			<div class="error message">❌️️&nbsp;This item has been deleted on <?= $deletedAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
		<?php endif; ?>
	</header>

	<nav class="itembuttons" data-for-item="<?=$this->e($item->getCode())?>">
		<?php if($editing && $target): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><?php if(!$containsMore): ?><button class="delete">❌&nbsp;Delete</button><?php endif ?>
		<?php elseif(!$adding && !$editing): ?>
			<?php if($deletedAt === null): ?><button class="addinside">📄&nbsp;Add</button><button class="edit">🛠️&nbsp;Edit</button><button class="clone">🔲&nbsp;Copy</button><?php endif ?><button class="history">📖&nbsp;History</button>
		<?php endif ?>
	</nav>

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

		<section class="product features">
			<?php $this->insert('features', ['features' => $product === null ? [] : $product->getFeatures()]) ?>
		</section>
	<?php else: ?>
		<section class="features">
			<?php $this->insert('features', ['features' => $features]) ?>
		</section>
	<?php endif ?>

	<section class="subitems">
		<?php
		if($adding && $target) {
			$this->insert('newItem', ['recursion' => true, 'innerrecursion' => false]);
		}

		$subitems = $item->getContents();
		foreach($subitems as $subitem) {
			$this->insert('item', array_merge($nextItemParameters, ['item' => $subitem]));
		}
		?>
	</section>
</article>
<?php if(($editing || $adding) && $target): ?>
	<script>const activate = true;</script>
	<?php $this->insert('editor');
endif;
?>
