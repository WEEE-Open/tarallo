<?php

namespace WEEEOpen\Tarallo;


trait ItemTraitFeatures {
	protected $features = [];

	/**
	 * Get a feature, or null if none.
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
	 * Get a feature value, or null if there is no feature (or has no value, if allowed).
	 *
	 * @param string $name Feature name
	 *
	 * @return string|int|double|null
	 */
	public function getFeatureValue(string $name) {
		$feature = $this->getFeature($name);
		if($feature === null) {
			return null;
		} else {
			return $feature->value;
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
		if(isset($this->product)) {
			$product = $this->product;
			/** @var Product $product */
			return array_merge($product->getFeatures(), $this->features);
		} else {
			return $this->features;
		}
	}

	/**
	 * @return Feature[]
	 */
	public function getOwnFeatures(): array {
		return $this->features;
	}

}
