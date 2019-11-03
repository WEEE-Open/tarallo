<?php


namespace WEEEOpen\Tarallo;


class SessionSSO {
	public $uid;
	public $cn;
	public $groups;
	public $idToken;
	public $idTokenExpiry = 0;
	public $idTokenValidityTime = 0;
	public $refreshToken;
	public $refreshTokenExpiry = 0;
	public $refreshTokenValidityTime = 0;
}
