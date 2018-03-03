<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Server\Feature[] $featuresProduct */
?>
<section class="features">
	<?php
	if(count($features) > 0): ?>
		<section class="remaining">
			<h3>All features (no grouping yet)</h3>
			<ul>
				<?php foreach($features as $feature):
					$featureID = $feature->name;
					?>
					<li>
						<div class="name"><?=$this->e($feature, 'printFeatureName')?></div>
						<div class="value" id="feature-edit-<?= $featureID ?>" contenteditable="true"><?=$this->makeEditable($this->e($feature, 'printFeatureValue'))?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</section>
