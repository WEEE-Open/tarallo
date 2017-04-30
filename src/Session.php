<?php

namespace WEEEOpen\Tarallo;
use WEEEOpen\Tarallo\Database\Database;

class Session {
	const COOKIE_NAME = 'tarallo';
	const SESSION_DURATION = 21600; // 6 hours
	const KEYSPACE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
	const KEYSPACE_STRLEN = 64;

	/**
	 * Starts a new session for the user, replacing any older session.
	 *
	 * @param User $user the authenticated user
	 * @param Database $db
	 */
	public static function start(User $user, Database $db) {
		$id = self::newUniqueIdentifier($db);
		self::setContent($id);
		$db->userDAO()->setSessionFromUser($user->getUsername(), $id, time() + self::SESSION_DURATION);
	}

	/**
	 * Create a new session identifier and check that it's unique.
	 * ...it will probably be, but check anyway.
	 *
	 * @param Database $db
	 *
	 * @return string
	 */
	private static function newUniqueIdentifier(Database $db) {
		do {
			$id = self::newIdentifier();
		} while($db->userDAO()->getUserFromSession($id) !== null);

		return $id;
	}

	/**
	 * Create a new session identifier.
	 *
	 * @return string
	 * @see newUniqueIdentifier
	 */
	private static function newIdentifier() {
		$str = '';
		for($i = 0; $i < 32; $i ++) {
			$str .= self::KEYSPACE[ random_int(0, self::KEYSPACE_STRLEN - 1) ];
		}

		return $str;
	}

	private static function setContent($newContent) {
		setcookie(self::COOKIE_NAME, $newContent);
	}

	/**
	 * Checks if there's a valid session in place and to which user it corresponds
	 *
	 * @param Database $db
	 *
	 * @return User|null the user, or null if not found (expired/invalid session, no cookie, etc...)
	 */
	public static function restore(Database $db) {
		if(isset($_COOKIE[ self::COOKIE_NAME ])) {
			$user = $db->userDAO()->getUserFromSession($_COOKIE[ self::COOKIE_NAME ]);
			if($user instanceof User) {
				$db->userDAO()->setSessionFromUser($user->getUsername(), $_COOKIE[ self::COOKIE_NAME ], time() + self::SESSION_DURATION);
			}
			return $user;
		}

		return null;
	}

	/**
	 * Ends session, logs out the user
	 *
	 * @param User $user
	 * @param Database $db
	 */
	public static function close(User $user, Database $db) {
		// Delete cookie
		setcookie(self::COOKIE_NAME, "", 1);
		$db->userDAO()->setSessionFromUser($user->getUsername(), null, null);
	}
}