<?php


namespace WEEEOpen\Tarallo;


trait ItemTraitOptionalFeatures {
	use ItemTraitFeatures;

	/**
	 * @param Feature|BaseFeature $feature
	 *
	 * @return $this
	 */
	public function addFeature($feature) {
		if(isset($this->features[$feature->name])) {
			if($feature instanceof BaseFeature) {
				// It's a BaseFeature (with no value) => ignore it, whatever
				return $this;
			} elseif($this->features[$feature->name] instanceof BaseFeature) {
				// $feature is not a BaseFeature (or we would be in the branch above), but the one it's replacing
				// is a BaseFeature: go ahead, replace it
			} else {
				// We're replacing a Feature with anything else: throw an exception as usual
				$name = $feature->name;
				throw new \InvalidArgumentException("Feature $name already added to " . (string) $this);
			}
		}
		$this->features[$feature->name] = $feature;
		return $this;
	}
}
