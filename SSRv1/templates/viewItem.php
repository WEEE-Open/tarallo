<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
if(!isset($recursion) || $recursion === false)
	$this->layout('internalPage', ['title' => 'Visualizza', 'user' => $user]) ?>

<article class="item">
	<section class="breadbox">
		<ul class="breadcrumbs">
			<?php foreach($item->getPath() as $piece): ?>
				<li><a href="/item/<?=$this->u($piece)?>"><?=$this->e($piece)?></a></li>
			<?php endforeach; ?>
		</ul>
		<!--<div class="breadsetter"><label>Set parent: <input></label></div>-->
	</section>

	<header>
		<h2><?=$this->e($item->getCode())?></h2>
	</header>

	<section class="features">
		<?php
		$features = $item->getCombinedFeatures();
		if(count($features) > 0): ?>
			<section>
				<h3><?=_('All features (no grouping, yet)')?></h3>
				<ul>
					<?php foreach($item->getCombinedFeatures() as $feature): ?>
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
