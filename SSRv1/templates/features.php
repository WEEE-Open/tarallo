<?php
/** @var \WEEEOpen\Tarallo\Server\Feature[] $features */
$groups = $this->getGroupedFeatures($this->getUltraFeatures($features));

if(count($features) > 0): ?>
	<?php foreach($groups as $groupTitle => $group): ?>
	<section>
		<h3><?=$groupTitle?></h3>
		<ul>
			<?php foreach($group as $feature): /** @var $feature \WEEEOpen\Tarallo\SSRv1\UltraFeature */ ?>
				<li>
					<div class="name"><span><?=$feature->name /* The span is a <label> in edit mode, we need an element here in view mode for css to work */?></span></div>
					<div class="value"><?=$this->contentEditableWrap($this->e($feature->value))?></div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php endforeach ?>
<?php endif; ?>
