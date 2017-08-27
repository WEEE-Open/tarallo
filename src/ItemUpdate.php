<?php

namespace WEEEOpen\Tarallo;

/**
 * Class ItemUpdate
 * "Patches" for items. Completely different from Item, even though extends Item.
 *
 * @package WEEEOpen\Tarallo
 */
class ItemUpdate extends Item implements \JsonSerializable {
	private $isDefault = false;
	private $parent = null;

	private $isDefaultChanged = false;
	private $defaultCodeChanged = false;
	private $parentChanged = false;
	private $featuresChanged = false;

	public function __construct($code) {
		parent::__construct($code, null);
	}

	public function setDefaultCode($code) {
		if($code === null) {
			$this->defaultCode = null;
		} else {
			$this->defaultCode = $this->sanitizeCode($code);
		}
		$this->defaultCodeChanged = true;
		return $this;
	}

	public function getDefaultCodeChanged() {
		return $this->defaultCodeChanged;
	}

	public function getIsDefaultChanged() {
		return $this->isDefaultChanged;
	}

	public function setIsDefault($is) {
		$this->isDefault = (bool) $is;
		$this->isDefaultChanged = true;
		return $this;
	}

	public function getIsDefault() {
		return $this->isDefault;
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

	public final function addFeatureDefault($name, $value) {
		throw new \LogicException('Cannot add default features to ItemUpdate as they will be ignored');
	}

	public final function addContent(Item $item) {
		throw new \LogicException('Cannot add Items inside ItemUpdate objects, use setParent() to move items instead');
	}

	public final function addAncestor($distance, $code) {
		throw new \LogicException('Cannot edit full ancestor sequence for item updates, use setParent instead');
	}

	public final function getAncestor($distance) {
		throw new \LogicException('Cannot edit full ancestor sequence for item updates, use getParent instead');
	}

	/**
	 * @return ItemIncomplete|null
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @param ItemIncomplete|null $parent
	 * @return ItemUpdate $this
	 */
	public function setParent($parent) {
		if($parent instanceof ItemIncomplete || $parent === null) {
			$this->parent = $parent;
		} else {
			throw new \InvalidArgumentException('Parent should be an instance of ItemIncomplete or null, ' . gettype($parent) . ' given');
		}
		$this->parentChanged = true;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getParentChanged() {
		return $this->parentChanged;
	}

	public function addFeature($name, $value) {
		$this->featuresChanged = true;
		return parent::addFeature($name, $value);
	}

	public function setFeaturesChanged() {
		$this->featuresChanged = true;
	}

	public function jsonSerialize() {
		$array = [];
		$array['code'] = $this->getCode();
		if(!empty($this->featuresChanged)) {
			$array['features'] = $this->getFeatures();
		}
		if($this->defaultCodeChanged) {
			$array['default'] = $this->defaultCode;
		}
		if($this->isDefaultChanged) {
			$array['is_default'] = $this->isDefault;
		}
		if($this->parentChanged) {
			$array['parent'] = $this->parent;
		}
		return $array;
	}
}