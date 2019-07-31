<?php


namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\BaseFeature;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class PartialSummaries {
	public static function summarizeCommercial(ItemWithFeatures $item) {
		$brand = $item->getFeature('brand');
		$manufacturer = $item->getFeature('brand-manufacturer');
		$model = $item->getFeature('model');
		$variant = $item->getFeature('variant');
		$internal = $item->getFeature('internal-name');

		$parts = [];
		if($brand !== null) {
			$parts[] = $brand;
		}
		if($manufacturer !== null) {
			$parts[] = "($manufacturer)";
		}
		if($model !== null) {
			$parts[] = $model;
		}
		if($internal !== null) {
			$parts[] = "($internal)";
		}
		if($variant !== null) {
			$parts[] = "$variant";
		}
		if(count($parts) <= 0) {
			return '';
		}
		$concat = implode(' ', $parts);
		$concat = str_replace(') (', ' ', $concat); // Asus (Nvidia) (GF17) => Asus (Nvidia GF17)
		return $concat;
	}
	
	public static function summarizePorts(ItemWithFeatures $item, bool $compact = false) {
		$filtered = self::getFeaturesInGroup($item, BaseFeature::GROUP_ports);
		$sequence = self::summarizeSequence($compact, $filtered);

		return implode(', ', $sequence);
	}

	public static function summarizeSockets(ItemWithFeatures $item, bool $compact = false) {
		$filtered = self::getFeaturesInGroup($item, BaseFeature::GROUP_sockets);
		$sequence = self::summarizeSequence($compact, $filtered);

		return implode(', ', $sequence);
	}

	public static function summarizePowerconnectors(ItemWithFeatures $item, bool $compact = false) {
		$filtered = self::getFeaturesInGroup($item, BaseFeature::GROUP_powerconnectors);
		$sequence = self::summarizeSequence($compact, $filtered);

		return implode(', ', $sequence);
	}

	/**
	 * @param ItemWithFeatures $item
	 * @param int $group
	 *
	 * @return Feature[]
	 */
	private static function getFeaturesInGroup(ItemWithFeatures $item, int $group): array {
		$filtered = [];
		foreach($item->getFeatures() as $feature) {
			// Skip BaseFeature(s) from ItemIncomplete, these cannot be summarized
			if($feature instanceof Feature
				&& isset(BaseFeature::groups[$feature->name])
				&& BaseFeature::groups[$feature->name] === $group) {
				$filtered[] = $feature;
			}
		}
		return $filtered;
	}

	/**
	 * @param bool $compact
	 * @param $filtered
	 *
	 * @return array
	 */
	private static function summarizeSequence(bool $compact, $filtered): array {
		$sequence = [];
		foreach($filtered as $feature) {
			$value = FeaturePrinter::printableValue($feature);
			$name = FeaturePrinter::printableName($feature->name);
			if($compact && $value === '1') {
				$sequence["3$name"] = $name;
			} else if(is_numeric($value)) {
				$sequence["3$name"] = "${value}× $name";
			} else if($feature->name === 'psu-connector-cpu') {
				// It's hardcoded, yes, it's identical in every human language, yes, it's a hack, yes
				$sequence["2$name"] = "$value CPU";
			} else if($feature->name === 'psu-connector-motherboard') {
				$sequence["1$name"] = "$value Mobo";
			}else {
				$sequence["0$name"] = "$value"; // e.g. "ATX 24 pin", it's obvious what it is an enum feature
			}
		}
		ksort($sequence);
		// This saves memory and provides a nicer result but it could be useless.
		// If you get here hunting for slow code with a profiler, consider removing it.
		$sequence = array_values($sequence);
		return $sequence;
	}
}
