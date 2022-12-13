<?php

namespace WEEEOpen\Tarallo;

class Feature extends BaseFeature
{
	/**
	 * Feature value
	 *
	 * @var string|int|double
	 */
	public $value;

	/**
	 * Feature constructor.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __construct($name, $value)
	{
		parent::__construct($name);
		self::validateValue($name, $value);
		$this->value = $value;
	}

	/**
	 * Get a feature with value of correct type,
	 * even if you only have it as string
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return Feature
	 */
	public static function ofString(string $name, $value)
	{
		BaseFeature::validateFeatureName($name);
		if ($value === '') {
			throw new \InvalidArgumentException("Feature $name cannot be an empty string");
		}
		switch (BaseFeature::getType($name)) {
			case BaseFeature::INTEGER:
				if (!is_numeric($value)) {
					throw new \InvalidArgumentException("Cannot cast feature $name to integer: $value is not numeric");
				}
				$value = (int) $value;
				if ($value === 0) {
					throw new \InvalidArgumentException("Feature $name = 0 has no meaning");
				}
				break;
			case BaseFeature::DOUBLE:
				if (!is_numeric($value)) {
					throw new \InvalidArgumentException("Cannot cast feature $name to double: $value is not numeric");
				}
				$value = (double) $value;
				if ($value === 0) {
					throw new \InvalidArgumentException("Feature $name = 0 has no meaning");
				}
				break;
		}
		return new self($name, $value);
	}

	/**
	 * Check that a value is valid
	 *
	 * @param string $name Feature name
	 * @param string|int|double $value Value
	 */
	private static function validateValue($name, $value)
	{
		$type = BaseFeature::getType($name);
		switch ($type) {
			case BaseFeature::STRING:
			case BaseFeature::MULTILINE:
				if (!is_string($value)) {
					throw new \InvalidArgumentException(
						'Feature value for ' . $name . ' must be string, ' . gettype($value) . ' given'
					);
				}
				break;
			case BaseFeature::INTEGER:
				if (!is_int($value)) {
					throw new \InvalidArgumentException(
						'Feature value for ' . $name . ' must be integer, ' . gettype($value) . ' given'
					);
				}
				if ($value < 0) {
					throw new \InvalidArgumentException(
						'Feature value for ' . $name . ' must be a positive integer, ' . $value . ' given'
					);
				}
				break;
			case BaseFeature::DOUBLE:
				if (!is_double($value)) {
					throw new \InvalidArgumentException(
						'Feature value for ' . $name . ' must be double, ' . gettype($value) . ' given'
					);
				}
				if ($value < 0) {
					throw new \InvalidArgumentException(
						'Feature value for ' . $name . ' must be a positive double, ' . $value . ' given'
					);
				}
				break;
			case BaseFeature::ENUM:
				if (!isset(BaseFeature::FEATURES[$name][$value])) {
					throw new \InvalidArgumentException(
						'Feature value for ' . $name . ' is not among acceptable ones: ' . $value . ' given'
					);
				}
				break;
			default:
				throw new \InvalidArgumentException('Unknown feature type: ' . $type);
		}
	}

	public function __toString()
	{
		return (string) $this->value;
	}
}
