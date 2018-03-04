<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Server\Feature[] $featuresProduct */
$groups = $this->getPrintableFeatures($features);
// TODO: same for $featuresProduct.
?>

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
						<select class="value" data-initial-value="<?= $this->e($ultra->feature->value, 'asTextContent')?>" id="feature-edit-<?= $ultra->feature->name ?>">
							<?php foreach($this->getOptions($ultra->feature) as $optionValue => $optionName): ?>
							<option value="<?= $optionValue ?>" <?= $optionValue === $ultra->feature->value ? 'selected' : '' ?>><?=$this->e($optionName)?></option>
							<?php endforeach ?>
						</select>
					<?php break; default: case \WEEEOpen\Tarallo\Server\Feature::STRING: ?>
						<div class="value" data-internal-type="s" data-internal-name="<?= $ultra->feature->name ?>" data-initial-value="<?= $this->e($ultra->feature->value, 'asTextContent') ?>" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
					<?php break; case \WEEEOpen\Tarallo\Server\Feature::INTEGER: ?>
						<div class="value" data-internal-type="i" data-internal-name="<?= $ultra->feature->name ?>" data-internal-value="<?= $ultra->feature->value ?>" data-previous-value="<?= $ultra->feature->value ?>" data-initial-value="<?= $ultra->feature->value ?>" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
					<?php break; case \WEEEOpen\Tarallo\Server\Feature::DOUBLE: ?>
						<div class="value" data-internal-type="d" data-internal-name="<?= $ultra->feature->name ?>" data-internal-value="<?= $ultra->feature->value ?>" data-previous-value="<?= $ultra->feature->value ?>" data-initial-value="<?= $ultra->feature->value ?>" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
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
<section class="new">
	<h3>New features</h3>
	<ul></ul>
</section>

<!--<section class="features default">
Moved to parent template
</section>-->

<template id="feature-edit-template-fractional-not-allowed">
	<section class="error message">Value must represent an integer number of base units<button>OK</button></section>
</template>
<template id="feature-edit-template-invalid-prefix">
	<section class="error message">Value outside range of known SI prefixes<button>OK</button></section>
</template>
<template id="feature-edit-template-empty-input">
	<section class="error message">Empty field not allowed<button>OK</button></section>
</template>
<template id="feature-edit-template-negative-input">
	<section class="error message">Negative values not allowed<button>OK</button></section>
</template>
<template id="feature-edit-template-string-start-nan">
	<section class="error message">Value must begin with a positive number<button>OK</button></section>
</template>
<template id="feature-edit-template-string-parse-nan">
	<section class="error message">Value must contain a number<button>OK</button></section>
</template>
