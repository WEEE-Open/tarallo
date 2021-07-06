<?php

namespace WEEEOpen\Tarallo\SSRv1;


use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;

class UltraFeature {
	protected $feature;
	public $name;
	public $pname;
	public $value;
	public $pvalue;
	public $group;
	public $type;

	private function __construct() {

	}

	/**
	 * UltraFeature 3000!
	 *
	 * Contains a Feature, its translated name, its pretty-printed value, its group, etc...
	 *
	 * @param Feature|BaseFeature $feature
	 * @param string $language
	 * @return UltraFeature
	 */
	public static function fromFeature(BaseFeature $feature, string $language): UltraFeature {
		$that = new UltraFeature();
		$that->feature = $feature;
		$that->type = $feature->type;
		$that->group = FeaturePrinter::printableGroup(BaseFeature::getGroup($feature->name));
		$that->name = $feature->name;
		$that->pname = FeaturePrinter::printableName($feature->name);
		if($feature instanceof Feature) {
			$that->value = $feature->value;
			$that->pvalue = FeaturePrinter::printableValue($feature);
		} else {
			$that->value = '';
			$that->pvalue = '';
		}
		return $that;
	}

	public static function printableValue(Feature $feature, string $language): string {
		return FeaturePrinter::printableValue($feature);
	}

	public function printableExplanation(string $language): ?string {
		return FeaturePrinter::printableExplanation($this->feature);
	}
}
