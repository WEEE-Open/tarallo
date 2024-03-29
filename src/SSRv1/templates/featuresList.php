<?php

$features = \WEEEOpen\Tarallo\SSRv1\FeaturePrinter::FEATURES;
$groups = [];
foreach ($features as $value => $name) {
	$groups[WEEEOpen\Tarallo\BaseFeature::getGroup($value)][$value] = $name;
}

ksort($groups);
foreach ($groups as &$group) {
	asort($group);
}

foreach ($groups as $groupTitle => $features) :
	?>
<optgroup label="<?=\WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableGroup($groupTitle)?>">
	<?php foreach ($features as $value => $name) : ?>
	<option value="<?=$value?>"><?=$name?></option>
	<?php endforeach ?>
</optgroup>
<?php endforeach ?>
