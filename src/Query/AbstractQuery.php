<?php
namespace WEEEOpen\Tarallo\Query;


abstract class AbstractQuery {
	protected $built = false;
	/** @var $parseFields QueryField[] */
	protected $parseFields;
	protected $user;

	function __construct() {
		$this->parseFields = $this->getParseFields();
	}

	abstract protected function getParseFields();

	protected function setBuilt() {
		if($this->isBuilt()) {
			throw new \LogicException('Query object already built');
		}
		$this->built = true;
	}

	protected function isBuilt() {
		return $this->built;
	}


	protected function normalizeString($string) {
		if(substr($string, 0, 1) === '/') {
			$string = substr($string, 1); // remove first slash
		}
		if(substr($string, - 1) === '/') {
			$string = substr($string, 0, strlen($string) - 1);
		}

		return $string;
	}
}