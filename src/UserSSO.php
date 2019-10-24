<?php


namespace WEEEOpen\Tarallo;


class UserSSO {
	public $uid;
	public $cn;
	//public $groups;
	public $idToken;
	public $idTokenExpiry = 0;
	public $refreshToken;
	public $refreshTokenExpiry = 0;

	public function getLevel() {
		return 0;
	}
}
