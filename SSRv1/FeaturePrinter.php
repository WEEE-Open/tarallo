<?php

namespace WEEEOpen\Tarallo\SSRv1;


use WEEEOpen\Tarallo\Server\Feature;

class FeaturePrinter {
	/**
	 * Pretty print a feature value, with unit and multiples and whatnot
	 *
	 * @param Feature $feature
	 *
	 * @return string
	 * @throws \InvalidArgumentException if it's not pretty-printable
	 */
	public static function prettyPrint(Feature $feature): string {
		$unit = self::getUnit($feature);
		$usePrefix = self::usePrefix($unit);

		if(!$usePrefix) {
			return $feature->value . ' ' . $unit;
		}

		if($unit === 'byte') {
			return self::binaryConvert($feature, 'B');
		}

		return self::decimalConvert($feature, $unit);
	}


	/**
	 * Get unit name, from feature name
	 *
	 * @param Feature $feature
	 *
	 * @return string
	 */
	private static function getUnit(Feature $feature): string {
		if(self::endsWith($feature->name, '-byte')) {
			return 'byte';
		} else if(self::endsWith($feature->name, '-hertz')) {
			return 'Hz';
		} else if(self::endsWith($feature->name, '-decibyte')) {
			return 'B';
		} else if(self::endsWith($feature->name, '-ampere')) {
			return 'A';
		} else if(self::endsWith($feature->name, '-volt')) {
			return 'V';
		} else if(self::endsWith($feature->name, '-watt')) {
			return 'W';
		} else if(self::endsWith($feature->name, '-inch')) {
			return 'in.';
		} else if(self::endsWith($feature->name, '-rpm')) {
			return 'rpm';
		} else if(self::endsWith($feature->name, '-mm')) {
			return 'mm';
		} else if(self::endsWith($feature->name, '-gram')) {
			return 'g';
		} else {
			throw new \InvalidArgumentException("Feature $feature is not pretty-printable");
		}
	}

	/**
	 * Does this unit use prefixes (k, M, G, ...)?
	 *
	 * Most of them do.
	 *
	 * @param string $unit
	 *
	 * @return bool
	 */
	private static function usePrefix(string $unit): bool {
		switch($unit) {
			case 'mm':
			case 'rpm':
			case 'in.':
				return false;
		}

		return true;
	}

	private static function endsWith(string $haystack, string $needle) {
		$length = strlen($needle); // It's O(1) internally, it has been like that for decades, don't worry

		if(strlen($haystack) < $length) {
			return false;
		} else {
			return substr($haystack, -$length) === $needle;
		}
	}

	/**
	 * Convert feature from base unit to prefixed unit, for bytes
	 *
	 * @param Feature $feature
	 * @param string $unit
	 *
	 * @return string
	 */
	private static function binaryConvert(Feature $feature, string $unit): string {
		$prefix = 0;
		$value = $feature->value;

		while($value >= 1024 && $prefix <= 6) {
			$value = $value / 1024; // Does this do a bit shift internally, for ints at least?
			$prefix++;
		}

		$i = $prefix > 0 ? 'i' : '';

		return $value . ' ' . self::unitPrefix($prefix, true) . $i . $unit;
	}

	/**
	 * Convert feature from base unit to prefixed unit, for normal decimal features
	 *
	 * @param Feature $feature
	 * @param string $unit
	 *
	 * @return string
	 */
	private static function decimalConvert(Feature $feature, string $unit): string {
		$prefix = 0;
		$value = $feature->value;

		while($value >= 1000 && $prefix <= 6) {
			// This casts ints to doubles, but JS does that too on the client (since JS has no ints) and it has never been a problem
			$value /= 1000;
			$prefix++;
		}
		return $value . ' ' . self::unitPrefix($prefix) . $unit;
	}

	/**
	 * Get prefix from int.
	 *
	 * @param int $prefix 0 = none, 1 = k, 2 = M, and so on
	 * @param bool $bigK Use uppercase K instead of the standardized lowercase k. Bytes apparently require an upper case K.
	 *
	 * @return string k, M, G, T, ...
	 */
	private static function unitPrefix(int $prefix, bool $bigK = false): string {
			switch($prefix) {
			case 0:
				return '';
			case 1:
				if($bigK) {
					return 'K';
				} else {
					return 'k';
				}
			case 2:
				return 'M';
			case 3:
				return 'G';
			case 4:
				return 'T';
			case 5:
				return 'P';
			case 6:
				return 'E';
			case -1:
				return 'm';
			//case -2:
			//	return 'µ';
			//case -3:
			//	return 'n';
		}
		throw new \InvalidArgumentException("Invalid SI prefix (value $prefix)");
	}
}
