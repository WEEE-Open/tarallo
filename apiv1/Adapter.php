<?php

namespace WEEEOpen\Tarallo\Server\v1;

use WEEEOpen\Tarallo\Server\AuthenticationException;
use WEEEOpen\Tarallo\Server\AuthorizationException;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Session;
use WEEEOpen\Tarallo\Server\User;
use WEEEOpen\Tarallo\Server\UserAnonymous;

class Adapter {
	public static function sessionWhoami(User $user, Database $db, $parameters, $querystring, $payload) {
		self::authorize($user);
		return ['username' => $user->getUsername()];
	}

	public static function sessionStart(User $user, Database $db, $parameters, $querystring, $payload) {
		Session::start($user, $db);
		return null;
	}

	public static function sessionClose(User $user, Database $db, $parameters, $querystring, $payload) {
		// If we ever add another level for e.g. banned users, this at least allows them to log out
		self::authenticate($user);
		Session::close($user, $db);
		return null;
	}

	public static function sessionRefresh(User $user, Database $db, $parameters, $querystring, $payload) {
		// The refresh itself has already been done by Session::restore, sooooo...
		self::authenticate($user);
		return null;
	}

	/**
	 * Check that an user is authorized (and authenticated too, or the entire thing won't make any sense)
	 *
	 * @param User|null $user Current, logged-in user. Or none if not authenticated.
	 * @param int $level Permission level required
	 * @see User::getLevel()
	 */
	private static function authorize(User $user, $level = 3) {
		self::authenticate($user);
		if($user->getLevel() > $level) {
			throw new AuthorizationException();
		}
	}

	/**
	 * Check that user is a valid user.
	 * You probably want authorize() instead, which also checks permission.
	 *
	 * @see Adapter::authorize()
	 * @param User $user
	 */
	private static function authenticate(User $user) {
		if($user instanceof UserAnonymous) {
			throw new AuthenticationException();
		}
	}
}