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
		$this->value = $this->getFeatureValue($feature);
		$this->name = Localizer::printableName($feature);
		$this->group = self::getGroup($feature->name);
	}

	/**
	 * Translate, pretty print or somehow "make pleasant to the eye" a feature value.
	 *
	 * @param Feature $feature
	 *
	 * @return string Value to be show to the user
	 */
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

	/**
	 * Group to which that feature belongs
	 *
	 * @param string $name Feature name (untranslated)
	 * @TODO: add a custom sorting function for groups, to use in uasort()
	 *
	 * @return string Translated group name
	 */
	public static function getGroup(string $name): string {
		if(strpos($name, '-ports-') > -1) {
			return 'Ports';
		} else if(strpos($name, '-sockets-') > -1) {
			return 'Sockets';
		} else {
			return 'Other';
		}
	}
}
