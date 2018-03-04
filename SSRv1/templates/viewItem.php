<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
if(!isset($recursion) || $recursion === false) {
	$recursion = false;
	$this->layout('main', ['title' => $this->e($item->getCode()), 'user' => $user, 'itembuttons' => true]);
} else {
	$recursion = true;
}

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
			$working = "working $value";
			break;
	}
	unset($value);
}

$subitemParameters = ['recursion' => true];
if(isset($edit)) {
	$subitemParameters['edit'] = $edit;
	$editing = true;
	if(strtolower($edit) === strtolower($item->getCode())) {
		$editingTarget = true;
	} else {
		$editingTarget = false;
	}
} else {
	$editing = false;
	$editingTarget = false;
}
?>

<?php if(!$recursion): ?>
<nav class="breadbox">
	<ul class="breadcrumbs">
		<?php foreach($item->getPath() as $piece): ?>
			<li><a href="/item/<?=$this->u($piece)?>"><?=$this->e($piece)?></a></li>
		<?php endforeach; ?>
	</ul>
	<!--<div class="breadsetter"><label>Set parent: <input></label></div>-->
</nav>
<?php endif ?>
<article class="item <?= $recursion ? '' : 'head' ?> <?= $working ?> <?= $editingTarget ? 'editing' : '' ?>" data-code="<?=$this->e($item->getCode())?>">
	<header>
		<h2 id="code-<?= $this->e($item->getCode()) ?>"><?=$this->e($item->getCode())?></h2>
	</header>

	<nav class="itembuttons" data-for-item="<?= $this->e($item->getCode()) ?>">
		<?php if($editing && $editingTarget): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><button class="delete">❌&nbsp;Delete</button>
		<?php elseif(!$editing): ?>
			<button class="addinside">📄&nbsp;Add</button><button class="edit">🛠️&nbsp;Edit</button>
		<?php endif ?>
	</nav>

	<?php if($editing && $editingTarget): ?>
		<section class="own features editing">
			<?php
			$this->insert('featuresEdit', ['features' => $item->getFeatures()]);
			?>
		</section>

		<section class="add">
			<label>Feature:
				<select>
				<?php $this->insert('allFeatures') ?>
				</select></label>
			<button>Add</button>
		</section>

		<section class="product features">

		</section>

		<section class="default features">
			<?php $this->insert('features', ['features' => $product === null ? [] : $product->getFeatures()]) ?>
		</section>

		<script src="/features.js"></script>
		<script src="/editor.js"></script>
	<?php else: ?>
		<section class="features">
			<?php $this->insert('features', ['features' => $features]) ?>
		</section>
	<?php endif ?>

	<section class="subitems">
		<?php
			$subitems = $item->getContents();
			foreach($subitems as $subitem) {
				$this->insert('viewItem', array_merge($subitemParameters, ['item' => $subitem]));
			}
		?>
	</section>
</article>
