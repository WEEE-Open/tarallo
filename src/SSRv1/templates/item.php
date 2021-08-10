<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var int $depth */
/** @var string|null $add */
/** @var string|null $edit */
/** @var bool $recursion */
/** @var string $self */
/** @var bool showProductButton */
$showProductButton = $showProductButton ?? true;
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
$summary_escaped = array_map([$this, 'e'], explode(', ', $summary));
unset($summary);

$product = $item->getProduct();
$missingProduct = false;
if($product === null) {
	if($item->getFeatureValue('brand') !== null && $item->getFeatureValue('model') !== null) {
		$missingProduct = true;
	}
} else {
	$productName = $this->e($product->getFullName());
}

$code_rawurlencoded = $this->e(rawurlencode($item->getCode()));
$code_escaped = $this->e($item->getCode());
$here = rtrim($self, '/') . '/';
?>

<?php if(!$recursion): $this->insert('breadcrumbs', ['item' => $item]); endif; ?>
<article class="container item<?=$recursion ? '' : ' root'?><?=$working?><?=$editing && $target ? ' head editing' : ''?><?= $deletedAt === null ? '' : ' deleted' ?>"
		data-code="<?=$code_escaped?>">
	<header class="row">
		<h4 class="p-2 col m-0" id="code-<?=$code_escaped?>"><?=$code_escaped?></h4>
		<nav class="p-2 m-0 ml-auto itembuttons">
			<?php if($editing): ?>
				<a class="btn btn-outline-secondary btn-sm btn-item disabled" role="button" href="#">
					<i class="fa fa-pencil-alt"></i>&nbsp;Rename
				</a>
			<?php else: ?>
				<?php if($deletedAt === null): ?>
				<button class="btn btn-outline-secondary btn-sm btn-item move" role="button" data-code="<?= $code_escaped ?>">
					<i class="fa fa-map-pin"></i>&nbsp;Move
				</button>
				<?php endif ?>
				<a class="btn btn-outline-secondary btn-sm btn-item" role="button" href="/item/<?= $code_rawurlencoded ?>/history">
					<i class="fa fa-history"></i>&nbsp;History
				</a>
			<?php endif ?>
		</nav>
	</header>

	<?php if($deletedAt === null): ?>
		<?php if($missingProduct): ?>
			<div class="inline-alert w-auto alert-serious" role="alert"><i class="fa fa-tag"></i>&nbsp;This item has no product: <a href="/new/product?split=<?=$code_escaped?>">create it now!</a></div>
		<?php endif ?>
		<?php if($item->getFeature('restrictions') !== null): ?>
			<div class="inline-alert w-auto alert-info" role="alert"><i class="fa fa-flag-checkered"></i>&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature
				($item->getFeature('restrictions'), $lang ?? 'en'))->pvalue; ?></div>
		<?php endif; ?>
		<?php if($item->getFeature('check') !== null): ?>
			<div class="inline-alert w-auto alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature
				($item->getFeature('check'), $lang ?? 'en'))->pvalue; ?></div>
		<?php endif; ?>
		<?php if($item->getFeature('todo') !== null): ?>
			<div class="inline-alert w-auto alert-info" role="alert"><i class="fa fa-hourglass-start"></i>&nbsp;<?= (WEEEOpen\Tarallo\SSRv1\UltraFeature::fromFeature
				($item->getFeature('todo'), $lang ?? 'en'))->pvalue; ?></div>
		<?php endif; ?>
	<?php else: ?>
		<div class="inline-alert w-auto alert-danger" role="alert"><i class="fa fa-trash"></i>&nbsp;This item has been deleted on <?= $deletedAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
	<?php endif; ?>
	<?php if($lostAt !== null): ?>
		<div class="inline-alert w-auto alert-serious" role="alert"><i class="fa fa-archive"></i>&nbsp;This item has been lost on <?= $lostAt->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d') ?></div>
	<?php endif; ?>

	<nav class="itembuttons row mx-0 mt-2">
		<?php if($editing && $target): ?>
			<button class="btn btn-outline-primary btn-item col-4 col-sm-auto mr-auto cancel" role="button">
				<i class="fa fa-arrow-circle-left"></i>&nbsp;Cancel
			</button>
			<button class="btn btn-success btn-item col-4 col-sm-auto save" role="button">
				<i class="fa fa-save"></i>&nbsp;Save
			</button>
			<?php if(!$containsMore): ?>
				<button class="btn btn-outline-primary btn-item col-4 col-sm-auto lost" role="button">
					<i class="fa fa-archive"></i>&nbsp;Lost
				</button>
				<button class="btn btn-outline-danger btn-item col-4 col-sm-auto delete" role="button">
					<i class="fa fa-trash"></i>&nbsp;Delete
				</button>
			<?php endif ?>
		<?php elseif(!$adding && !$editing): ?>
			<?php if($deletedAt === null): ?>
				<a class="btn btn-outline-primary btn-item col-6 col-sm-4 col-md-auto" role="button" href="<?= $here ?>edit/<?= $code_rawurlencoded ?>?from=<?= rawurlencode($here) ?>">
					<i class="fa fa-edit"></i>&nbsp;Edit
				</a>
				<a class="btn btn-outline-primary btn-item col-6 col-sm-4 col-md-auto" role="button" href="/new/item?copy=<?= $code_rawurlencoded ?>">
					<i class="fa fa-clone"></i>&nbsp;Clone
				</a>
				<a class="btn btn-outline-primary btn-item col-6 col-sm-4 col-md-auto" role="button" href="<?= $here ?>add/<?= $code_rawurlencoded ?>?from=<?= rawurlencode($here) ?>">
					<i class="fa fa-plus-circle"></i>&nbsp;Add
				</a>
				<?php if($product === null && $item->getFeatureValue('brand') !== null && $item->getFeatureValue('model') !== null && $item->getFeatureValue('variant') !== null): ?>
				<a class="btn btn-outline-primary btn-item col-6 col-sm-4 col-md-auto" role="button" href="/new/product?split=<?= $code_rawurlencoded ?>">
					<i class="fa fa-adjust"></i>&nbsp;Split
				</a>
				<?php endif ?>
			<?php endif ?>
			<a class="btn btn-outline-primary btn-item col-6 col-sm-4 col-md-auto" data-toggle="collapse" href="#collapsible-features-<?=$code_escaped?>" role="button" aria-expanded="false" aria-controls="#collapsible-features-<?=$code_escaped?>">
				<i class="fa fa-globe"></i>&nbsp;Details
			</a>
			<?php if($showProductButton && $product !== null): ?>
				<a class="btn btn-outline-primary btn-item col-12 col-sm-8 col-md-10 col-lg-auto" role="button" href="/product/<?=$this->e(rawurlencode($product->getBrand()))?>/<?=$this->e(rawurlencode($product->getModel()))?>/<?=$this->e(rawurlencode($product->getVariant()))?>">
					<i class="fa fa-briefcase"></i>&nbsp;View <?= $this->e($productName) ?>
				</a>
			<?php endif ?>
		<?php endif ?>
	</nav>

	<?php if(!$editing || !$target): ?>
        <section class="summary <?=$working?> open">
			<span><?= implode('<span class="sep">, </span></span><span>', $summary_escaped) ?></span>
        </section>

		<section class="features collapse" id="collapsible-features-<?=$this->e($item->getCode())?>">
			<?php $this->insert('features', ['features' => $features, 'product' => $item->getProduct() === null ? [] : $item->getProduct()->getFeatures()]) ?>
		</section>
	<?php else: ?>
		<?php if($item->getProduct() !== null): ?>
			<section class="product features">
				<?php
				$this->insert('features', ['features' => $item->getProduct()->getFeatures()]);
				?>
			</section>
		<?php endif ?>

		<section class="own features editing">
			<?php
			$this->insert('featuresEdit', ['features' => $item->getOwnFeatures()]);
			?>
		</section>

		<section class="addfeatures">
			<label>Feature:
				<select class="allfeatures ml-2 form-control">
				</select></label>
			<button class="btn btn-primary ml-2">Add</button>
		</section>
	<?php endif ?>

	<section class="subitems">
		<?php
		if($adding && $target) {
			$empty = new \WEEEOpen\Tarallo\ItemIncomplete(null);
			$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('brand'));
			$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('model'));
			$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('variant'));
			$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('type'));
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
