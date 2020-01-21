<?php

namespace WEEEOpen\Tarallo;

/**
 * Products, once called "default items".
 *
 * @package WEEEOpen\Tarallo
 */
class Product extends ProductCode implements \JsonSerializable {
	use ItemTraitFeatures;

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

	function jsonSerialize() {
		$array = [];
		$array['brand'] = $this->brand;
		$array['model'] = $this->model;
		$array['variant'] = $this->variant;
		$array['features'] = $this->getFeatures();
		return $array;
	}

	function __toString() {
		if($this->variant === self::DEFAULT_VARIANT) {
			return $this->brand . ' ' . $this->model;
		} else {
			return $this->brand . ' ' . $this->model . '(' . $this->variant . ')';
		}
	}
}
