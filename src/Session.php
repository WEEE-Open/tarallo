<?php

namespace WEEEOpen\Tarallo;

class Session {
	const COOKIE_NAME = 'tarallo';

	/**
	 * Starts a new session for the user, replacing any older session.
	 *
	 * @param User $user the authenticated user
	 *
	 * @throws DatabaseException
	 */
	public function start(User $user) {
		$id = $this->newIdentifier();
		$this->setContent($id);
		// TODO: store $id in database or throw database exception
	}

	private function newIdentifier() {
		// TODO: better random string
		return mt_rand();
	}

	private function setContent($newContent) {
		setcookie(self::COOKIE_NAME, $newContent);
	}

	/**
	 * Checks if there's a valid session in place and to which user it corresponds
	 *
	 * @return User che user, or null if not found (expired/invalid session, no cookie, etc...)
	 * @throws DatabaseException
	 */
	public function restore() {
		if(isset($_COOKIE[ self::COOKIE_NAME ])) {
			// TODO: query database, return false if doesn't match
			// $this->user = $user;
			//return $user;
		}

		return null;
	}

	/**
	 * Ends session, logs out the user
	 *
	 * @throws DatabaseException
	 */
	public function close() {
		// Delete cookie
		setcookie(self::COOKIE_NAME, "", 1);
		// TODO: delete session ID from database
	}
}