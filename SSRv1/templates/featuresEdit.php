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
						<?php if($ultra->feature->type === \WEEEOpen\Tarallo\Server\Feature::ENUM): ?>
							<select class="value" id="feature-edit-<?= $ultra->feature->name ?>">
								<?php foreach($this->getOptions($ultra->feature) as $optionValue => $optionName): ?>
								<option value="<?= $optionValue ?>" <?= $optionValue === $ultra->feature->value ? 'selected' : '' ?>><?=$this->e($optionName)?></option>
								<?php endforeach ?>
							</select>
						<?php else: ?>
							<div class="value" data-internal-value="<?= $this->e($ultra->feature->value) ?>" id="feature-edit-<?= $ultra->feature->name ?>" contenteditable="true"><?=$this->contentEditableWrap($this->e($ultra->value))?></div>
						<?php endif ?>
							<div class="controls"><button>❌</button></div>
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
