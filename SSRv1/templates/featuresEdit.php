<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Server\Feature[] $featuresProduct */
$groups = $this->getPrintableFeatures($features);
// TODO: same for $featuresProduct.
?>
<section class="features own">
	<?php
	if(count($features) > 0):
		foreach($groups as $groupTitle => $group): ?>
		<section>
			<h3><?=$groupTitle?></h3>
			<ul>
				<?php foreach($group as $ultra): /** @var $ultra \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
					<li>
						<div class="name"><label for="feature-edit-<?= $ultra->feature->name ?>"><?=$ultra->name?></label></div>
						<?php switch($ultra->feature->type): case \WEEEOpen\Tarallo\Server\Feature::ENUM: ?>
							<select class="value" data-previous-value="<?= $this->e($ultra->feature->value, 'asTextContent')?>" id="feature-edit-<?= $ultra->feature->name ?>">
								<?php foreach($this->getOptions($ultra->feature) as $optionValue => $optionName): ?>
								<option value="<?= $optionValue ?>" <?= $optionValue === $ultra->feature->value ? 'selected' : '' ?>><?=$this->e($optionName)?></option>
								<?php endforeach ?>
							</select>
						<?php break; default: case \WEEEOpen\Tarallo\Server\Feature::STRING: ?>
							<div class="value" data-internal-type="s" data-internal-name="<?= $ultra->feature->name ?>" data-previous-value="<?= $this->e($ultra->feature->value, 'asTextContent') ?>" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
						<?php break; case \WEEEOpen\Tarallo\Server\Feature::INTEGER:case \WEEEOpen\Tarallo\Server\Feature::DOUBLE: ?>
							<div class="value" data-internal-type="n" data-internal-name="<?= $ultra->feature->name ?>" data-internal-value="<?= $ultra->feature->value ?>" data-previous-value="<?= $ultra->feature->value ?>" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
						<?php endswitch; ?>
							<div class="controls"><button data-name="<?= $ultra->feature->name ?>" class="delete">❌</button></div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php
		endforeach;
	endif;
	?>
</section>

<section class="features default">

</section>
