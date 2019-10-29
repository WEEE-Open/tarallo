<?php


namespace WEEEOpen\Tarallo;


class User {
	public $uid;
	public $cn;

	private function __construct() {
	}

	public static function fromSession(SessionSSO $session): User {
		$user = new User();
		$user->cn = $session->cn;
		$user->uid = $session->uid;
		return $user;
	}

	public function getLevel() {
		// TODO: implement
		return 0;
	}
}
