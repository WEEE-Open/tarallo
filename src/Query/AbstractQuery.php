<?php
namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

abstract class AbstractQuery {
	protected $built = false;
	protected $queryFields = [];

	/**
	 * @param $query string representing query type (Login, Location, etc...)
	 * @param $parameter string parameters passed to QueryField constructor
	 *
	 * @return Field\QueryField|null
	 */
	abstract protected function queryFieldsFactory($query, $parameter);

	public final function fromString($string, $requestBody = null) {
		if(!is_string($string) || $string === '') {
			throw new InvalidParameterException('Query string must be a non-empty string');
		}

		$pieces = explode('/', $this->normalizeString($string));
		$this->fromPieces($pieces, $requestBody);

		$this->setBuilt();

		return $this;
	}

	abstract protected function fromPieces($pieces, $requestBody);

	protected final function setBuilt() {
		if($this->isBuilt()) {
			throw new \LogicException('Query object already built');
		}
		$this->built = true;
	}

	protected final function isBuilt() {
		return $this->built;
	}

	protected function addQueryField($name, Field\QueryField $qf) {
		$this->queryFields[$name] = $qf;
	}

	protected function getAllQueryFields() {
		return $this->queryFields;
	}

	protected function getQueryField($name) {
		if(isset($this->queryFields[$name])) {
			return $this->queryFields[$name];
		} else {
			return null;
		}
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

	abstract public function run($user, Database $db);
}