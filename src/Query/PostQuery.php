<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;

class PostQuery extends AbstractQuery {
	const FIELD_LOGIN = 'Login';
	const FIELD_EDIT = 'Edit';
	private $queryFields = [];

	protected function queryFieldsFactory($query, $parameter) {
		switch($query) {
			case self::FIELD_LOGIN:
				return new Field\Login($parameter);
			case self::FIELD_EDIT:
				return new Field\Edit($parameter);
			default:
				throw new \InvalidArgumentException('Unknown field ' . $query);
		}
	}

	protected function fromPieces($pieces, $requestBody) {
		if(count($pieces) > 1) {
			throw new \InvalidArgumentException('POST queries only allow one field, ' . count($pieces) . ' given');
		}
		$field = $pieces[0];

		$this->addQueryField($field, $this->queryFieldsFactory($field, $requestBody));

		return $this;
	}

	protected function addQueryField($name, Field\QueryField $qf) {
		// limit to one...
		$this->queryFields = [$name => $qf];
	}

	public function __toString() {
		$query = $this->getAllQueryFields();
		if(empty($query)) {
			return '{}';
		} else {
			list($content) = $query;
			return (string) $content;
		}
	}

	/**
	 * @param Tarallo\User|null $user current user ("recovered" from session)
	 * @param Tarallo\Database $database
	 *
	 * @return array data for the response
	 * @throws \LogicException if the query hasn't been built or an unknown field is encountered (which shouldn't happen here)
	 * @todo return a Response object?
	 */
	public function run($user, Tarallo\Database $database) {
		if(!$this->isBuilt()) {
			throw new \LogicException('Cannot run a query without building it first');
		}

		if(!$this->isBuilt()) {
			throw new \LogicException('Trying to run an empty query');
		}

		if($this->queryFields instanceof Field\Login) {
			return [];
		} else if($this->queryFields instanceof Field\Edit) {
			if($user === null) {
				throw new \Exception('Authentication needed');
			}
			throw new \Exception('Not implemented (yet)');
		} else {
			throw new \LogicException('Cannot convert object of class ' . get_class($user) . ' to database query (should never happen)');
		}

		//return [];
	}
}