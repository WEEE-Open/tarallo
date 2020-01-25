<?php
/** @var \WEEEOpen\Tarallo\Feature[] $features */
/** @var \WEEEOpen\Tarallo\Feature[] $product */
if(isset($features['variant']) && $features['variant']->value === \WEEEOpen\Tarallo\Product::DEFAULT_VARIANT) {
	unset($features['variant']);
}
$groups = $this->getGroupedFeatures($this->getUltraFeatures($features));

if(count($features) > 0): ?>
	<?php foreach($groups as $groupTitle => $group): ?>
	<section>
		<h3><?=$groupTitle?></h3>
		<ul>
			<?php foreach($group as $feature): /** @var $feature \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
				<li>
					<div class="name"><span><?=$feature->pname /* The span is a <label> in edit mode, we need an element here in view mode for css to work */?></span></div>
                    <?php if(isset($product[$feature->name]) && $product[$feature->name]->value !== $feature->value): ?>
					    <div class="value"><div><del><?=$this->e(\WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableValue($product[$feature->name]))?></del>&nbsp;<?=$this->e($feature->pvalue)?></div></div>
                    <?php else: ?>
                        <div class="value"><?=$this->contentEditableWrap($this->e($feature->pvalue))?></div>
                    <?php endif ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php endforeach ?>
<?php endif; ?>
