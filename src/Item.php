<?php
namespace WEEEOpen\Tarallo;


class Item implements \JsonSerializable {
	private $code;
	private $features = [];
	private $content = [];

	function __construct($code) {
		if(!is_string($code)) {
			throw new \LogicException('Item code must be a string');
		}
		$this->code = $code;
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
		if(!self::featureNameIsValid($name)) {
			throw new \LogicException('Feature name must be a string, ' . gettype($name) . ' given');
		}
		if(!self::featureValueIsValid($value)) {
			throw new \LogicException('Feature value must be a string or positive integer, ' . gettype($name) . ' given');
		}
		if(isset($this->features[$name])) {
			throw new InvalidParameterException('Feature ' . $name . ' already inserted into item ' . (string) $this);
		}

		$this->features[$name] = $value;
		return $this;
	}

	public function addMultipleFeatures($features) {
		if(!is_array($features)) {
			throw new \LogicException('Features must be passed as an array');
		}
		foreach($features as $name => $value) {
			$this->addFeature($name, $value);
		}
		return $this;
	}

	public function addChild(Item $item) {
		$this->content = $item;
	}

	function jsonSerialize() {
		return [
			'code' => $this->code,
			'features' => $this->features,
			'content' => $this->content,
		];
	}
}