<?php

namespace WEEEOpen\Tarallo\Server;


class ItemFeatures extends ItemIncomplete {
	protected $features = [];

	function __construct($code) {
		parent::__construct($code);
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