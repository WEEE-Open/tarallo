<?php
namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database;

abstract class AbstractQuery {
	protected $built = false;

	abstract public function fromString($string);

	protected final function setBuilt() {
		if($this->isBuilt()) {
			throw new \LogicException('Query object already built');
		}
		$this->built = true;
	}

	protected final function isBuilt() {
		return $this->built;
	}

	abstract public function run($user, Database $db);
}