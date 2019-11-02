<?php


namespace WEEEOpen\Tarallo;


class User {
	public $uid;
	public $cn;
	private $level;
	const AUTH_LEVEL_ADMIN = 0;
	const AUTH_LEVEL_RW = 2;
	const AUTH_LEVEL_RO = 3;

	private function __construct() {
	}

	public static function fromSession(SessionSSO $session): User {
		$user = new User();
		$user->cn = $session->cn;
		$user->uid = $session->uid;
		if(count(array_intersect(TARALLO_OIDC_READ_ONLY_GROUPS, $session->groups))) {
			$user->level = self::AUTH_LEVEL_RO;
		} elseif(count(array_intersect(TARALLO_OIDC_ADMIN_GROUPS, $session->groups))) {
			$user->level = self::AUTH_LEVEL_ADMIN;
		} else {
			$user->level = self::AUTH_LEVEL_RW;
		}
		return $user;
	}

	public function getLevel() {
		return $this->level;
	}
}
