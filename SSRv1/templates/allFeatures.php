<?php
$features = \WEEEOpen\Tarallo\SSRv1\Localizer::allNames();
//asort($features);
$groups = [];
foreach($features as $value => $name) {
	$groups[\WEEEOpen\Tarallo\SSRv1\UltraFeature::getGroup($value)][$value] = $name;
}

ksort($groups);
foreach($groups as &$group) {
	asort($group);
}

foreach($groups as $groupTitle => $features):
?>
<optgroup label="<?=$groupTitle?>">
	<?php foreach($features as $value => $name): ?>
	<option value="<?=$value?>"><?=$name?></option>
	<?php endforeach ?>
<?php endforeach ?>
</optgroup>
