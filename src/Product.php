<?php

namespace WEEEOpen\Tarallo;

/**
 * Products, once called "default items".
 *
 * @package WEEEOpen\Tarallo
 */
class Product implements \JsonSerializable {
	use ItemTraitFeatures;
	const DEFAULT_VARIANT = null;
	private $brand;
	private $model;
	private $variant;

	/**
	 * Product constructor.
	 *
	 * @param string $brand
	 * @param string $model
	 * @param string $variant
	 */
	public function __construct(string $brand, string $model, ?string $variant = self::DEFAULT_VARIANT) {
		$this->brand = $brand;
		$this->model = $model;
		$this->variant = $variant;
	}

	function jsonSerialize() {
		$array = [];
		$array['features'] = $this->getFeatures();
		return $array;
	}

	function __toString() {
		if($this->variant === self::DEFAULT_VARIANT) {
			return $this->model . ' ' . $this->model;
		} else {
			return $this->model . ' ' . $this->model . '(' . $this->variant . ')';
		}
	}

	/**
	 * @return string
	 */
	public function getBrand(): string {
		return $this->brand;
	}

	/**
	 * @return string
	 */
	public function getModel(): string {
		return $this->model;
	}

	/**
	 * @return string|null
	 */
	public function getVariant(): ?string {
		return $this->variant;
	}
}
