<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
?>
<section class="features">
	<?php
	if(count($features) > 0): ?>
		<section class="remaining">
			<h3>All features (no grouping yet)</h3>
			<ul>
				<?php foreach($features as $feature): ?>
					<li>
						<div class="name"><?=$this->e($feature, 'printFeatureName')?></div>
						<div class="value"><?=$this->e($feature, 'printFeatureValue')?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</section>
