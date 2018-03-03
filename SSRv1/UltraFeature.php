<?php

namespace WEEEOpen\Tarallo\SSRv1;


use WEEEOpen\Tarallo\Server\Feature;

class UltraFeature {
	public $feature;
	public $name;
	public $value;

	/**
	 * UltraFeature 3000!
	 *
	 * Contains a Feature, its translated name and its pretty-printed value.
	 *
	 * @param Feature $feature
	 * @param string $language
	 */
	public function __construct(Feature $feature, string $language) {
		$this->feature = $feature;
		$this->value = $this->getFeatureValue($feature);
		$this->name = Localizer::printableName($feature);
	}

	private function getFeatureValue(Feature $feature) {
		if($feature->type === Feature::INTEGER || $feature->type === Feature::DOUBLE) {
			try {
				return FeaturePrinter::prettyPrint($feature);
			} catch(\InvalidArgumentException $ignored) {

			}
		} else if($feature->type === Feature::ENUM) {
			return Localizer::printableValue($feature->name, $feature->value);
		}

		return $feature->value;
	}
}
