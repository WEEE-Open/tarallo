<?php

namespace WEEEOpen\Tarallo\Server;

/**
 * Products, once called "default items".
 *
 * @package WEEEOpen\Tarallo
 */
class Product implements \JsonSerializable {
	use ItemTraitFeatures;
	const DEFAULT_VARIANT = '0';
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
	public function __construct($brand, $model, $variant = self::DEFAULT_VARIANT) {
		$this->brand = $brand;
		$this->model = $model;
		$this->variant = $variant;
	}

	function jsonSerialize() {
		$array = [];

		return $array;
	}

	function __toString() {
		if($this->variant === self::DEFAULT_VARIANT) {
			return $this->model . ' ' . $this->model;
		} else {
			return $this->model . ' ' . $this->model . '(' . $this->variant . ')';
		}
	}
}