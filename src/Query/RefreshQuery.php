<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

class RefreshQuery extends PostJSONQuery implements \JsonSerializable {
    protected function parseContent($content) {
    }

    function jsonSerialize() {
	    return [];
    }

	/**
	 * @param Tarallo\User|null $user current user ("recovered" from session)
	 * @param Tarallo\Database\Database $database
	 *
	 * @return array data for the response
	 * @throws InvalidParameterException if no session exists (or is expired)
	 */
	public function run($user, Database $database) {
		if($user === null) {
			return ['username' => null];
		} else {
			return ['username' => $user->getUsername()];
		}
	}
}