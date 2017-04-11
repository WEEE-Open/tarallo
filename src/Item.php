<?php
namespace WEEEOpen\Tarallo;


class Item extends ItemIncomplete implements \JsonSerializable {
	private $features = [];
	private $featuresDefault = [];
	private $content = [];

	private static function featureNameIsValid($name) {
		if(is_string($name)) {
			return true;
		} else {
			return false;
		}
	}

	private static function featureValueIsValid($value) {
		// values are all unsigned ints in the database
		if(is_string($value) || (is_int($value) && $value >= 0)) {
			return true;
		} else {
			return false;
		}
	}

	public function addFeature($name, $value) {
		$this->checkFeature($name, $value);
		$this->features[$name] = $value;
		return $this;
	}

	private function checkFeature($name, $value) {
		if(!self::featureNameIsValid($name)) {
			throw new \InvalidArgumentException('Feature name must be a string, ' . gettype($name) . ' given');
		}
		if(!self::featureValueIsValid($value)) {
			throw new \InvalidArgumentException('Feature value must be a string or positive integer, ' . gettype($name) . ' given');
		}
		if(isset($this->features[$name])) {
			throw new InvalidParameterException('Feature ' . $name . ' already inserted into item ' . (string) $this);
		}
	}

	public function addMultipleFeatures($features) {
		if(!is_array($features)) {
			throw new \InvalidArgumentException('Features must be passed as an array');
		}
		foreach($features as $name => $value) {
			$this->addFeature($name, $value);
		}
		return $this;
	}

	public function getFeatures() {
		return $this->features;
	}

	public function addChild(Item $item) {
		$this->content[] = $item;
		return $this;
	}

	public function getChildren() {
		return $this->content;
	}

	function jsonSerialize() {
		return [
			'code' => parent::getCode(),
			'features' => $this->features,
			'content' => $this->content,
		];
	}

	public function __toString() {
		if(isset($this->content['type'])) {
			$type = $this->content['type'];
			return parent::getCode() . " ($type)";
		} else {
			return parent::getCode();
		}
	}

	public function addFeatureDefault($name, $value) {
		$this->checkFeature($name, $value);
		$this->featuresDefault[$name] = $value;
		return $this;
	}


	public function __clone() {
		foreach(get_object_vars($this) as $prop => &$array) {
			if(is_array($array)) {
				foreach($array as &$value) {
					if(is_object($value)) {
						$value = clone $value;
					}
				}
			}
		}
	}
}