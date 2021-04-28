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
	 * @param string|null $feature
	 *
	 * @return $this
	 */
	public function setFeature(?string $feature): ExceptionWithFeature {
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
	 * @return $this
	 */
	public function setFeatureValue($featureValue): ExceptionWithFeature {
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
