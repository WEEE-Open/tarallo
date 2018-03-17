<?php

namespace WEEEOpen\Tarallo\SSRv1;


use WEEEOpen\Tarallo\Server\Feature;

class UltraFeature {
	public $feature;
	public $name;
	public $value;
	public $group;

	/**
	 * UltraFeature 3000!
	 *
	 * Contains a Feature, its translated name,its pretty-printed value and its group.
	 *
	 * @param Feature $feature
	 * @param string $language
	 */
	public function __construct(Feature $feature, string $language) {
		$this->feature = $feature;
		$this->value = FeaturePrinter::getFeatureValue($feature);
		$this->name = FeaturePrinter::printableName($feature);
		$this->group = FeaturePrinter::getGroup($feature->name);
	}
}
