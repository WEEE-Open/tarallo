<?php

namespace WEEEOpen\Tarallo\Server;


trait ItemTraitFeatures {
	protected $features = [];

	/**
	 * Get a feature, or null if none.
	 * Uses combined features (item and product).
	 *
	 * @param string $name Feature name
	 *
	 * @return Feature|BaseFeature|null
	 */
	public function getFeature(string $name) {
		$features = $this->getFeatures();
		if(isset($features[$name])) {
			return $features[$name];
		} else {
			return null;
		}
	}

	/**
	 * @param Feature $feature
	 *
	 * @return $this
	 */
	public function addFeature($feature) {
		if(isset($this->features[$feature->name])) {
			throw new \InvalidArgumentException('Feature ' . $feature->name . ' already added to ' . (string) $this);
		}
		$this->features[$feature->name] = $feature;

		return $this;
	}

	/**
	 * Experimental method, may be removed, do not rely on it (yet)
	 *
	 * @param string $featureName
	 *
	 * @return $this
	 */
	public function removeFeatureByName(string $featureName) {
		unset($this->features[$featureName]);

		return $this;
	}

	/**
	 * @return Feature[]
	 */
	public function getFeatures(): array {
		return $this->features;
	}
}
