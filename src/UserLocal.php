<?php

namespace WEEEOpen\Tarallo;

class UserLocal extends User
{
	public $owner;

	public static function fromSession(SessionLocal $session): User
	{
		$user = new UserLocal();
		$user->cn = $session->description;
		$user->uid = $session->owner;
		$user->owner = $session->owner;
		$user->level = $session->level;

		return $user;
	}
}
