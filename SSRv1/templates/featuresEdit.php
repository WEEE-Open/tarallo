<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Server\Feature[] $featuresProduct */
$features = $this->getPrintableFeatures($features);
// TODO: same for $featuresProduct
?>
<section class="features">
	<?php
	if(count($features) > 0): ?>
		<section class="remaining">
			<h3>All features (no grouping yet)</h3>
			<ul>
				<?php foreach($features as $ultra): /** @var $ultra \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
					<li>
						<div class="name"><label for="feature-edit-<?= $ultra->feature->name ?>"><?=$this->e($ultra->name)?></label></div>
						<?php if($ultra->feature->type === \WEEEOpen\Tarallo\Server\Feature::ENUM): ?>
							<select class="value" id="feature-edit-<?= $ultra->feature->name ?>">
								<?php foreach($this->getOptions($ultra->feature) as $optionValue => $optionName): ?>
								<option value="<?= $optionValue ?>" <?= $optionValue === $ultra->feature->value ? 'selected' : '' ?>><?=$this->e($optionName)?></option>
								<?php endforeach ?>
							</select>
						<?php else: ?>
							<div class="value" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
						<?php endif ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</section>
