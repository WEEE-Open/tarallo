<?php
namespace WEEEOpen\Tarallo;


class Item extends ItemIncomplete implements \JsonSerializable {
	private $features = [];
	private $featuresDefault = [];
	private $content = [];
	private $defaultCode = null;

	public function __construct($code, $defaultCode = null) {
		parent::__construct($code);
		if($defaultCode !== null) {
			$this->defaultCode = $this->sanitizeCode($defaultCode);
		}
	}

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
		$this->addFeatureInternal($name, $value, $this->features);
		return $this;
	}

	public function addFeatureDefault($name, $value) {
		$this->addFeatureInternal($name, $value, $this->featuresDefault);
		return $this;
	}

	private function addFeatureInternal($name, $value, &$array) {
		$this->checkFeature($name, $value, $array);
		$array[$name] = $value;
	}

	private function checkFeature($name, $value, $array) {
		if(!self::featureNameIsValid($name)) {
			throw new \InvalidArgumentException('Feature name must be a string, ' . gettype($name) . ' given');
		}
		if(!self::featureValueIsValid($value)) {
			throw new \InvalidArgumentException('Feature value must be a string or positive integer, ' . gettype($name) . ' given');
		}
		if(isset($array[$name])) {
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

	public function getFeaturesDefault() {
		return $this->featuresDefault;
	}

	public function getDefaultCode() {
		return $this->defaultCode;
	}

	public function addChild(Item $item) {
		$this->content[] = $item;
		return $this;
	}

	public function getChildren() {
		return $this->content;
	}

	public function jsonSerialize() {
		$array = [
			'code' => parent::getCode()
		];
		if(!empty($this->features)) {
			$array['features'] = $this->features;
		}
		if(!empty($this->content)) {
			$array['content'] = $this->content;
		}
		if(!empty($this->featuresDefault)) {
			$array['features_default'] = $this->featuresDefault;
		}
		if($this->defaultCode !== null) {
			$array['default'] = $this->defaultCode;
		}

		return $array;
	}

	public function __toString() {
		if(isset($this->features['type'])) {
			return parent::getCode() . ' (' . $this->features['type'] . ')';
		} else if(isset($this->featuresDefault['type'])) {
			return parent::getCode() . ' (' . $this->featuresDefault['type'] . ')';
		} else {
			return parent::getCode();
		}
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