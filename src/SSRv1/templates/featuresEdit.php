<?php
/** @var \WEEEOpen\Tarallo\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Feature[] $featuresProduct */
?>

<?php
if(count($features) > 0):
	$ultras = $this->getUltraFeatures($features);
	foreach($ultras as $ultra) {
	    // Names of all the ultraFeatures to insert
	    $ultraNames[$ultra->name] = true;
    }
	$groups = $this->getGroupedFeatures($ultras);
	// TODO: same for $featuresProduct.

	foreach($groups as $groupTitle => $group): ?>
	<section>
		<h3><?=$groupTitle?></h3>
		<ul>
			<?php foreach($group as $ultra): /** @var $ultra \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
				<li class="feature-edit-<?= $ultra->name ?> feature-edit">
					<div class="name"><label for="feature-edit-<?= $ultra->name ?>"><?=$ultra->pname?></label></div>
					<?php switch($ultra->type): case WEEEOpen\Tarallo\BaseFeature::ENUM: ?>
						<select class="value" autocomplete="off" data-internal-name="<?= $ultra->name ?>" data-internal-type="e" data-initial-value="<?= $this->e($ultra->value, 'asTextContent')?>" id="feature-edit-<?= $ultra->name ?>">
							<?php if($ultra->value == null): ?><option value="" disabled selected></option><?php endif; ?>
							<?php foreach($this->getOptions($ultra->name) as $optionValue => $optionName): ?>
							<option value="<?= $optionValue ?>" <?= $optionValue === $ultra->value ? 'selected' : '' ?>><?=$this->e($optionName)?></option>
							<?php endforeach ?>
						</select>
					<?php break; default: case WEEEOpen\Tarallo\BaseFeature::STRING: ?>
						<div class="value" data-internal-type="s" data-internal-name="<?= $ultra->name ?>" data-initial-value="<?= $this->e($ultra->value) ?>" id="feature-edit-<?= $ultra->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->pvalue))?></div>
					<?php break; case WEEEOpen\Tarallo\BaseFeature::INTEGER: ?>
						<div class="value" data-internal-type="i" data-internal-name="<?= $ultra->name ?>" data-internal-value="<?= $ultra->value ?>" data-previous-value="<?= $ultra->value ?>" data-initial-value="<?= $ultra->value ?>" id="feature-edit-<?= $ultra->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->pvalue))?></div>
					<?php break; case WEEEOpen\Tarallo\BaseFeature::DOUBLE: ?>
						<div class="value" data-internal-type="d" data-internal-name="<?= $ultra->name ?>" data-internal-value="<?= $ultra->value ?>" data-previous-value="<?= $ultra->value ?>" data-initial-value="<?= $ultra->value ?>" id="feature-edit-<?= $ultra->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->pvalue))?></div>
					<?php endswitch; ?>
						<div class="controls"><button data-name="<?= $ultra->name ?>" class="delete" tabindex="-1">❌</button></div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php
	endforeach;
endif;
?>
<section class="newfeatures">
	<h3>New features</h3>
	<ul></ul>
</section>
