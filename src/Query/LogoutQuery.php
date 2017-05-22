<?php
namespace WEEEOpen\Tarallo\Query;

use WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\Database\Database;

class LogoutQuery extends AbstractQuery {
	public function __construct($string) {}

	public function run($user, Database $database) {
		if($user === null) {
			return null;
		} else {
			$database->userDAO()->endSession($user);
			return null;
		}
	}
}
