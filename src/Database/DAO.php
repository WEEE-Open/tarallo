<?php

namespace WEEEOpen\Tarallo\Database;

abstract class DAO
{
	protected $database;
	private $callback;

	public function __construct(Database $db, $callback)
	{
		$this->database = $db;
		$this->callback = $callback;
	}

	/**
	 * @return \PDO the PDO instance from Database
	 */
	protected function getPDO()
	{
		return call_user_func($this->callback);
	}
}
