<?php

namespace WEEEOpen\Tarallo;


class ItemUpdate extends Item implements \JsonSerializable {
	private $isDefault = false;

	public function setDefaultCode($code) {
		if($code === null) {
			$this->defaultCode = null;
		} else {
			$this->defaultCode = $this->sanitizeCode($code);
		}
		return $this;
	}

	public function setIsDefault($is) {
		$this->isDefault = (bool) $is;
		return $this;
	}

	protected static function featureValueIsValid($value) {
		if(parent::featureValueIsValid($value)) {
			return true;
		} else if($value === null) {
			return true;
		} else {
			return false;
		}
	}

	public function addFeatureDefault($name, $value) {
		throw new \LogicException('Cannot add default features to ItemUpdate as they will be ignored');
	}
}