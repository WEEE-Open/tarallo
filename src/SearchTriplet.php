<?php

namespace WEEEOpen\Tarallo;

class SearchTriplet implements \JsonSerializable, \ArrayAccess
{
	private $feature;
	private $compare;
	//private static $separators = ['>', '>=', '=', '<=', '<', '<>', '~', '≥', '≤', '!='];
	public const SEPARATORS_ORDERING = ['>', '>=', '<=', '<'];
	public const SEPARATORS_PARTIAL = ['~', '!~'];
	public const SEPARATORS_OTHER = ['=', '<>'];
	public const SEPARATORS_DEFINED = ['*', '!'];

	public function __construct($key, $compare, $value)
	{
		if ($value === null) {
			if (in_array($compare, self::SEPARATORS_DEFINED)) {
				$this->feature = new BaseFeature($key);
			} else {
				throw new \InvalidArgumentException("Value must be provided for operator '$compare'");
			}
		} else {
			$this->feature = new Feature($key, $value);
		}

		if (in_array($compare, self::SEPARATORS_PARTIAL)) {
			self::checkCanPartialMatch($this->feature);
		} elseif (in_array($compare, self::SEPARATORS_ORDERING)) {
			self::checkWellOrdered($this->feature, $compare);
		} elseif (!in_array($compare, self::SEPARATORS_OTHER) && !in_array($compare, self::SEPARATORS_DEFINED)) {
			throw new \InvalidArgumentException("'$compare' is not a valid comparison operator");
		}

		$this->compare = $compare;
	}

	public function __toString()
	{
		return $this->getKey() . $this->getCompare() . $this->getValue();
	}

	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->feature->name;
	}

	/**
	 * @return string
	 */
	public function getCompare(): string
	{
		return $this->compare;
	}

	/**
	 * @return string
	 */
	public function getValue(): ?string
	{
		if ($this->feature instanceof Feature) {
			return $this->feature->value;
		} else {
			return null;
		}
	}

	/**
	 * @return Feature|BaseFeature
	 */
	public function getAsFeature()
	{
		return $this->feature;
	}

	private static function checkCanPartialMatch(Feature $feature)
	{
		if ($feature->type !== BaseFeature::STRING) {
			throw new \InvalidArgumentException(
				'Cannot partially match feature ' . $feature->name . ': not a text feature'
			);
		}
	}

	private static function checkWellOrdered(Feature $feature, $operator)
	{
		if ($feature->type !== BaseFeature::INTEGER && $feature->type !== BaseFeature::DOUBLE) {
			throw new \InvalidArgumentException(
				"Cannot apply operator '$operator' to " . $feature->name . ': cannot be ordered'
			);
		}
	}

	public function jsonSerialize()
	{
		return [$this->feature->name, $this->compare, $this->getValue()];
	}

	public function offsetExists($offset): bool
	{
		if ($offset < 0 || $offset > 2) {
			return false;
		}
		return true;
	}

	public function offsetGet($offset)
	{
		switch ($offset) {
			case 0:
				return $this->feature->name;
			case 1:
				return $this->compare;
			case 2:
				return $this->feature->value;
			default:
				throw new \LogicException("SearchTriplet index out of bound");
		}
	}

	public function offsetSet($offset, $value)
	{
		throw new \LogicException("The array interface is read-only");
	}

	public function offsetUnset($offset)
	{
		throw new \LogicException("The array interface is read-only");
	}
}
