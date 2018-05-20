<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Server\Feature[] $featuresProduct */
?>

<?php
if(count($features) > 0):
	$groups = $this->getPrintableFeatures($features);
	// TODO: same for $featuresProduct.

	foreach($groups as $groupTitle => $group): ?>
	<section>
		<h3><?=$groupTitle?></h3>
		<ul>
			<?php foreach($group as $ultra): /** @var $ultra \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
				<li class="feature-edit-<?= $ultra->feature->name ?>">
					<div class="name"><label for="feature-edit-<?= $ultra->feature->name ?>"><?=$ultra->name?></label></div>
					<?php switch($ultra->feature->type): case \WEEEOpen\Tarallo\Server\Feature::ENUM: ?>
						<select class="value" autocomplete="off" data-internal-name="<?= $ultra->feature->name ?>" data-internal-type="e" data-initial-value="<?= $this->e($ultra->feature->value, 'asTextContent')?>" id="feature-edit-<?= $ultra->feature->name ?>">
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
						<div class="controls"><button data-name="<?= $ultra->feature->name ?>" class="delete" tabindex="-1">❌</button></div>
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
