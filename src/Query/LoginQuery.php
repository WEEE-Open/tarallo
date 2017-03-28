<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\InvalidParameterException;

class LoginQuery extends AbstractQuery {
	protected function fromPieces($pieces, $requestBody) {
		if(count($pieces) > 1) {
			throw new InvalidParameterException('POST queries only allow one field, ' . count($pieces) . ' given');
		}
		$field = $pieces[0];

		$this->addQueryField($field, $this->queryFieldsFactory($field, $requestBody));

		return $this;
	}

    public function fromString($string)
    {
        // TODO: Implement fromString() method.
    }

	protected function addQueryField($name, Field\QueryField $qf) {
		// limit to one...
		if(count($this->getAllQueryFields()) > 0) {
			throw new \LogicException('Trying to submit multiple queries in a single POST request');
		}
		parent::addQueryField($name, $qf);
	}

	public function __toString() {
		$query = $this->getAllQueryFields();
		if(empty($query)) {
			return '{}';
		} else {
			$content = array_pop($query);
			return (string) $content;
		}
	}

	/**
	 * @param Tarallo\User|null $user current user ("recovered" from session)
	 * @param Tarallo\Database $database
	 *
	 * @return array data for the response
	 * @throws \Exception because some stuff isn't implemented (yet)
	 * @todo return a Response object?
	 */
	public function run($user, Tarallo\Database $database) {
		if(!$this->isBuilt()) {
			throw new \LogicException('Cannot run a query without building it first');
		}

		$fields = $this->getAllQueryFields();
		if(empty($fields)) {
			throw new \LogicException('Trying to run an empty query');
		}
		$query = reset($fields);

		if($query instanceof Field\Login) {
			$login = $query->getContent();
			$newUser = $database->getUserFromLogin($login['username'], $login['password']);
			if($newUser === null) {
				throw new InvalidParameterException('Wrong username or password');
			}
			Tarallo\Session::start($newUser, $database);
			return [];
		} else if($query instanceof Field\Edit) {
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