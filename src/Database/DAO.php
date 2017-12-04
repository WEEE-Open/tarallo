<?php

namespace WEEEOpen\Tarallo\Database;

abstract class DAO {
	protected $database;
	private $callback;

	public function __construct(Database $db, $callback) {
		$this->database = $db;
		$this->callback = $callback;
	}

	/**
	 * @return \PDO the PDO instance from Database
	 */
	protected function getPDO() {
		return call_user_func($this->callback);
	}

	/**
	 * Generate the IN part of an SQL query.
	 * The part that goes between "WHERE x IN (" and ")", more specifically.
	 *
	 * @param string $prefix Any prefix, possibly beginning with ":" to use in PDO
	 * @param array $array array of values that will be inserted/selected from database. This is used to count the
	 * query parameters(?) and keys are appended to the prefix.
	 *
	 * @return string|bool resulting string, or a random "false" if substr somehow fails.
	 */
	protected static function multipleIn($prefix, $array) {
		$in = '';
		foreach($array as $k => $v) {
			if(!is_integer($k)) {
				throw new \InvalidArgumentException('Keys should be integers, ' . $k . ' isn\'t');
			}
			$in .= $prefix . $k . ', ';
		}

		return substr($in, 0, strlen($in) - 2); //remove last ', '
	}

	/**
	 * Is it an array and does it contain anything?
	 * Exactly what it says on the tin.
	 *
	 * @param $something
	 *
	 * @return bool
	 */
	protected static function isArrayAndFull($something) {
		if(is_array($something) && !empty($something)) {
			return true;
		} else {
			return false;
		}
	}
}