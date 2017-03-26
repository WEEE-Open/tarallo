<?php
namespace WEEEOpen\Tarallo\Query\Field;

use WEEEOpen\Tarallo\InvalidParameterException;

class Sort extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		$pieces = explode(",", $parameter);
		$keys = [];
		$this->content = [];

		foreach($pieces as $piece) {
			if(strlen($piece) < 2) {
				throw new InvalidParameterException($piece . ' (contained in ' . $parameter . ') should be at least 2 characters long (+ or - for sort order & a key)');
			}
			$order = substr($piece, 0, 1);
			$key = substr($piece, 1);

			if($order !== '+' && $order !== '-') {
				throw new InvalidParameterException('Sort order "' . $order . '" (contained in ' . $piece . ') must be + or -');
			}

			if(in_array($key, $keys)) {
				throw new InvalidParameterException('Sort parameter ' . $parameter . ' contains duplicate key: ' . $key);
			}

			// TODO: check that $key is a valid key
			$keys[] = $key;
			$this->content[$key] = $order;
		}
	}

	public function __toString() {
		$result = [];
		foreach($this->getContent() as $key => $order) {
			$result[] = $order . $key;
		}
		return '/Sort/' . implode(',', $result);
	}
}