<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

class LoginQuery extends PostJSONQuery implements \JsonSerializable {
    private $username;
    private $password;
    private $login = true;

    protected function parseContent($content) {
    	if($content['username'] === null && $content['password'] === null) {
    		$this->login = false;
    		return;
	    }

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
	    return ['username' => $this->username, 'password' => $this->password];
    }

	/**
	 * @param Tarallo\User|null $user current user ("recovered" from session)
	 * @param Tarallo\Database\Database $database
	 *
	 * @return array data for the response
	 * @throws \Exception because some stuff isn't implemented (yet)
	 * @todo return a Response object?
	 */
	public function run($user, Database $database) {
		if($this->login) {
			$newUser = $database->userDAO()->getUserFromLogin($this->username, $this->password);
			if($newUser === null) {
				throw new InvalidParameterException('Wrong username or password');
			} else {
				Tarallo\Session::start($newUser, $database);
				return [];
			}
		} else {
			if($user instanceof Tarallo\User) {
				Tarallo\Session::close($user, $database);
			}
			return [];
		}
	}
}