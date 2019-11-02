<?php


namespace WEEEOpen\Tarallo;


trait ExceptionWithFeature {
	/**
	 * @var string|null
	 */
	protected $feature = null;
	/**
	 * @var mixed $featureValue
	 */
	protected $featureValue = null;

	/**
	 * @param $feature
	 *
	 * @return $this
	 */
	public function setFeature(?string $feature) {
		$this->feature = $feature;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getFeature(): ?string {
		return $this->feature;
	}

	/**
	 * @param mixed $featureValue
	 *
	 * @return ExceptionWithFeature
	 */
	public function setFeatureValue($featureValue) {
		$this->featureValue = $featureValue;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFeatureValue() {
		return $this->featureValue;
	}
}
