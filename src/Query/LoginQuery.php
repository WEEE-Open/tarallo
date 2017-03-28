<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\InvalidParameterException;

class LoginQuery extends PostJSONQuery implements \JsonSerializable {
    private $username;
    private $password;

    protected function parseContent($content) {
        if(!isset($content['username']) || !isset($content['password'])) {
            throw new InvalidParameterException('Request body must contain "username" and "password"');
        }

        $this->username = (string) $content['username'];
        $this->password = (string) $content['password'];

        if($this->username === '') {
            throw new InvalidParameterException('Username cannot be empty');
        }
        if($this->password === '') {
            throw new InvalidParameterException('Password cannot be empty');
        }
    }

    function jsonSerialize() {
        if($this->isBuilt()) {
            return [];
        } else {
            throw new \LogicException('Cannot serialize query without building it first');
        }
    }

    public function __toString() {
        return json_encode($this);
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

        $newUser = $database->getUserFromLogin($this->username, $this->password);
        if($newUser === null) {
            throw new InvalidParameterException('Wrong username or password');
        } else {
            Tarallo\Session::start($newUser, $database);
            return [];
        }
	}
}