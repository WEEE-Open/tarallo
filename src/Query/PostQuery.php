<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;

class PostQuery extends AbstractQuery {
	protected $query = null;

	protected function getParseFields() {
		return [
			'Login'    => new QueryFieldLogin(),
			'Edit'     => new QueryFieldEdit(),
		];
	}

	public function fromString($string, $requestBody) {
		if(!is_string($string) || $string === '') {
			throw new \InvalidArgumentException('Query string must be a non-empty string');
		}

		$this->setBuilt();

		$pieces = explode('/', $this->normalizeString($string));
		if(count($pieces) > 1) {
			throw new \InvalidArgumentException('POST queries only allow one field, ' . count($pieces) . ' given');
		}
		$field = $pieces[0];

		if(isset($this->parseFields[ $field ])) {
			$this->parseFields[ $field ]->parse($requestBody);
			$this->query = $this->parseFields[ $field ];
		} else {
			throw new \InvalidArgumentException('Unknown field ' . $field);
		}

		return $this;
	}

	public function __toString() {
		if($this->query instanceof QueryFieldPostJSON) {
			return (string) $this->query;
		} else {
			return '{}';
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
			throw new \LogicException('Trying to run an empty query');
		}

		if($this->query === null || !($this->query instanceof QueryField)) {
			throw new \LogicException('Trying to run a uninitialized query');
		}

		if($this->query instanceof QueryFieldLogin) {
			return [];
		} else if($this->query instanceof QueryFieldEdit) {
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