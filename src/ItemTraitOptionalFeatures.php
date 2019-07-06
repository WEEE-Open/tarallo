<?php


namespace WEEEOpen\Tarallo\Server;


trait ItemTraitOptionalFeatures {
	use ItemTraitFeatures;

	/**
	 * @param Feature|BaseFeature $feature
	 *
	 * @return $this
	 */
	public function addFeature($feature) {
		if(isset($this->features[$feature->name])) {
			if(!($feature instanceof BaseFeature)) {
				$name = $feature->name;
				throw new \InvalidArgumentException("Feature $name already added to " . (string) $this);
			}
		}
		$this->features[$feature->name] = $feature;

		return $this;
	}
}