<?php

namespace WEEEOpen\Tarallo\Server;


class ItemFeatures extends ItemIncomplete {
	protected $features = [];

	function __construct($code) {
		parent::__construct($code);
	}

	/**
	 * Get a feature, or null if none.
	 * Uses combined features (item and product).
	 *
	 * @param string $name Feature name
	 *
	 * @return Feature|null
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
	public function addFeature(Feature $feature) {
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
	 */
	public function removeFeature(string $featureName) {
		unset($this->features[$featureName]);
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function deleteFeature($name) {
		unset($this->features[$name]);

		return $this;
	}

	/**
	 * @param array $features
	 *
	 * @return $this
	 */
	public function addMultipleFeatures(array $features) {
		foreach($features as $feature) {
			$this->addFeature($feature);
		}

		return $this;
	}

	/**
	 * @return Feature[]
	 */
	public function getFeatures() {
		return $this->features;
	}
}
