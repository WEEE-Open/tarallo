<?php

namespace WEEEOpen\Tarallo;

/**
 * Class ItemDefault
 * Default items. They're simply Item objects, with some properties missing (e.g. no ancestors, no default code).
 * is_default is always set to true in JSON serialization
 *
 * @package WEEEOpen\Tarallo
 */
class ItemDefault extends Item implements \JsonSerializable {
	public function __construct($code) {
		if($code === null) {
			throw new \InvalidArgumentException('Cannot create a default item (ItemDefault) without a code');
		}
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