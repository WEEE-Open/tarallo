<?php

namespace WEEEOpen\Tarallo\SSRv1;


use WEEEOpen\Tarallo\Server\BaseFeature;
use WEEEOpen\Tarallo\Server\Feature;

class UltraFeature {
	public $feature;
	public $name;
	public $value;
	public $group;

	private function __construct() {

	}

	/**
	 * UltraFeature 3000!
	 *
	 * Contains a Feature, its translated name,its pretty-printed value and its group.
	 *
	 * @param Feature $feature
	 * @param string $language
	 * @return UltraFeature
	 */
	public static function fromFeature(Feature $feature, string $language): UltraFeature {
		$that = new UltraFeature();
		$that->feature = $feature;
		$that->value = FeaturePrinter::printableValue($feature);
		$that->name = FeaturePrinter::printableName($feature->name);
		$that->group = FeaturePrinter::printableGroup(BaseFeature::getGroup($feature->name));
		return $that;
	}

	public static function printableValue(Feature $feature, string $language) {
		return FeaturePrinter::printableValue($feature);
	}

	public static function fromEmpty(string $name) {
		$that = new UltraFeature();
		// Mocking other classes...
		// TODO: use FeatureBase, remove mocking mockery
		$that->feature = new \stdClass();
		$that->feature->name = $name;
		$that->feature->value = '';
		$that->feature->type = BaseFeature::getType($name);
		$that->value = '';
		$that->name = FeaturePrinter::printableName($name);
		$that->group = FeaturePrinter::printableGroup(BaseFeature::getGroup($name));
		return $that;
	}
}
