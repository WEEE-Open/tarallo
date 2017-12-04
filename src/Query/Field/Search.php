<?php

namespace WEEEOpen\Tarallo\Query\Field;

use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Query\SearchTriplet;

class Search extends Multifield implements QueryField {
	public function __construct($parameter) {
		$pieces = explode(',', $parameter);
		foreach($pieces as $piece) {
			$this->add($piece);
		}
	}

	public function add($parameter) {
		parent::add($this->toTriplet($parameter));
	}

	private function toTriplet($parameter) {
		$parameter = (string) $parameter;
		$separator = SearchTriplet::getSeparators();
		$i = 0;
		do {
			$pieces = explode($separator[$i], $parameter);
			if(count($pieces) === 2 && strlen($pieces[0]) > 0 && strlen($pieces[1]) > 0) {
				// TODO: check that key is a valid key
				return new SearchTriplet($pieces[0], $separator[$i], $pieces[1]);
			}
			$i ++;
		} while($i < count($separator));
		throw new InvalidParameterException($parameter . ' must be a key-value pair separated by an "=", ">" o "<"');
	}

	public function __toString() {
		$result = '';
		foreach($this->getContent() as $triplet) {
			$result .= '/Search/' . (string) $triplet;
		}

		return $result;
	}
}