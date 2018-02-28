<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
if(!isset($recursion) || $recursion === false) {
	$recursion = false;
	$this->layout('internalPage', ['title' => 'Visualizza', 'user' => $user]);
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

?>

<article class="item <?= $recursion ? '' : 'head' ?> <?= $working ?>">
	<nav class="breadbox">
		<ul class="breadcrumbs">
			<?php foreach($item->getPath() as $piece): ?>
				<li><a href="/item/<?=$this->u($piece)?>"><?=$this->e($piece)?></a></li>
			<?php endforeach; ?>
		</ul>
		<!--<div class="breadsetter"><label>Set parent: <input></label></div>-->
	</nav>

	<header>
		<h2><?=$this->e($item->getCode())?></h2>
	</header>

	<section class="features">
		<?php
		if(count($features) > 0): ?>
			<section>
				<h3>All features (no grouping yet)</h3>
				<ul>
					<?php foreach($features as $feature): ?>
						<li>
							<div class="name"><?=$feature->name?></div>
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
