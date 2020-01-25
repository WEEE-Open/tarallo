<?php

namespace WEEEOpen\Tarallo;

/**
 * Products, once called "default items".
 *
 * @package WEEEOpen\Tarallo
 */
class Product extends ProductCode implements \JsonSerializable, ItemWithFeatures {
	use ItemTraitFeatures;

	function jsonSerialize() {
		$array = [];
		$array['brand'] = $this->brand;
		$array['model'] = $this->model;
		$array['variant'] = $this->variant;
		$array['features'] = [];
		if(!empty($this->features)){
			foreach($this->features as $features) {
				$array['features'][$features->name] = $features->value;
			}
		}
		return $array;
	}

	function __toString() {
		if($this->variant === self::DEFAULT_VARIANT) {
			return $this->brand . ' ' . $this->model;
		} else {
			return $this->brand . ' ' . $this->model . ' (' . $this->variant . ')';
		}
	}
}
