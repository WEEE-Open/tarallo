<?php

namespace WEEEOpen\Tarallo\Server;

class SearchTriplet {
	private $feature;
	private $compare;
	//private static $separators = ['>', '>=', '=', '<=', '<', '<>', '~', '≥', '≤', '!='];
	const separatorsOrdering = ['>', '>=', '<=', '<'];
	const separatorsPartial = ['~', '!~'];
	const separatorsOther = ['=', '<>'];

	public function __construct($key, $compare, $value) {
		$this->feature = new Feature($key, $value);
		
		if(in_array($compare, self::separatorsPartial)) {
			self::checkCanPartialMatch($this->feature);
		} else if(in_array($compare, self::separatorsOrdering)) {
			self::checkWellOrdered($this->feature, $compare);
		} else if(!in_array($compare, self::separatorsOther)) {
			throw new \InvalidArgumentException("'$compare' is not a valid comparison operator");
		}

		$this->compare = $compare;
	}

	public function __toString() {
		return $this->getKey() . $this->getCompare() . $this->getValue();
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->feature->name;
	}

	/**
	 * @return string
	 */
	public function getCompare() {
		return $this->compare;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->feature->value;
	}

	/**
	 * @return Feature
	 */
	public function getAsFeature() {
		return $this->feature;
	}

	private static function checkCanPartialMatch(Feature $feature) {
		if($feature->type !== Feature::STRING) {
			throw new \InvalidArgumentException('Cannot partially match feature ' . $feature->name . ': not a text feature');
		}
	}

	private static function checkWellOrdered(Feature $feature, $operator) {
		if($feature->type !== Feature::INTEGER && $feature->type !== Feature::DOUBLE) {
			throw new \InvalidArgumentException("Cannot apply operator '$operator' to " . $feature->name . ': cannot be ordered');
		}
	}
}