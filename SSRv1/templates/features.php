<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
$features = $this->getPrintableFeatures($features);
?>
<section class="features">
	<?php
	if(count($features) > 0): ?>
		<section class="remaining">
			<h3>All features (no grouping yet)</h3>
			<ul>
				<?php foreach($features as $feature): /** @var $feature \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
					<li>
						<div class="name"><?=$this->e($feature->name)?></div>
						<div class="value"><?=$this->e($feature->value)?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</section>
