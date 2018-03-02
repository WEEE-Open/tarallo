<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
if(!isset($recursion) || $recursion === false) {
	$recursion = false;
	$this->layout('main', ['title' => 'Visualizza', 'user' => $user, 'itembuttons' => true]);
} else {
	$recursion = true;
}

$features = $item->getCombinedFeatures();

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

$editing = $edit ?? false;

if(isset($edit) && strtolower($edit) === strtolower($item->getCode())) {
	$editingTarget = true;
} else {
	$editingTarget = false;
}
?>

<nav class="breadbox">
	<ul class="breadcrumbs">
		<?php foreach($item->getPath() as $piece): ?>
			<li><a href="/item/<?=$this->u($piece)?>"><?=$this->e($piece)?></a></li>
		<?php endforeach; ?>
	</ul>
	<!--<div class="breadsetter"><label>Set parent: <input></label></div>-->
</nav>
<article class="item <?= $recursion ? '' : 'head' ?> <?= $working ?>">
	<header>
		<h2 id="code-<?= $this->e($item->getCode()) ?>"><?=$this->e($item->getCode())?></h2>
	</header>

	<nav class="itembuttons" data-for-item="<?= $this->e($item->getCode()) ?>">
		<?php if($editing && $editingTarget): ?>
			<button class="save">❌&nbsp;Save</button><button class="cancel">❌&nbsp;Save</button><button class="delete">❌&nbsp;Delete</button>
		<?php elseif(!$editing): ?>
			<button class="addinside">📄&nbsp;Add</button><button class="edit">🛠️&nbsp;Edit</button>
		<?php endif ?>
	</nav>

	<section class="features">
		<?php
		if(count($features) > 0): ?>
			<section class="remaining">
				<h3>All features (no grouping yet)</h3>
				<ul>
					<?php foreach($features as $feature): ?>
						<li>
							<div class="name"><?=$this->printFeatureName($feature)?></div>
							<div class="value"><?=$this->printFeatureValue($feature)?></div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</section>

	<section class="subitems">
		<?php
			$subitems = $item->getContents();
			foreach($subitems as $subitem) {
				$this->insert('viewItem', ['item' => $subitem, 'user' => $user, 'recursion' => true]);
			}
		?>
	</section>
</article>
