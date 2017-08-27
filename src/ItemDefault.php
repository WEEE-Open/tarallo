<?php

namespace WEEEOpen\Tarallo;


class ItemDefault extends Item implements \JsonSerializable {
	public function __construct($code) {
		parent::__construct($code, null);
	}

	public function addFeatureDefault($name, $value) {
		throw new \LogicException('Trying to add default features to a default Item (use addFeature() instead)');
	}

	public function addAncestor($distance, $code) {
		throw new \LogicException('Default items cannot have ancestors');
	}

	public function getAncestor($distance) {
		return null;
	}

	function jsonSerialize() {
		$array = parent::jsonSerialize();
		$array['is_default'] = true;
		return $array;
	}
}