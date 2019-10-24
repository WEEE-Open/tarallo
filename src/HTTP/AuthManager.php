<?php

namespace WEEEOpen\Tarallo\HTTP;

use Jumbojett\OpenIDConnectClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\User;
use WEEEOpen\Tarallo\UserSSO;

class AuthManager implements Middleware {
	const COOKIE_NAME = 'tsessionsso';
	const KEYSPACE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
	const KEYSPACE_STRLEN = 64;

	/**
	 * Set the cookie
	 *
	 * @param $newContent
	 * @param $expire
	 */
	protected static function setCookie(string $newContent, int $expire) {
		setcookie(self::COOKIE_NAME, $newContent, $expire, '', '', !TARALLO_DEVELOPMENT_ENVIRONMENT, true);
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
		} while($db->userDAO()->sessionExists($id));

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
		for($i = 0; $i < 32; $i++) {
			try {
				$str .= self::KEYSPACE[random_int(0, self::KEYSPACE_STRLEN - 1)];
			} catch(\Exception $e) {
				// Okay PHPStorm, will you stop complaining now? Please?
				echo 'Not enough entropy';
				exit(1);
			}
		}

		return $str;
	}

	// TODO: split this thing. One checks if user is logged in BEFORE routing and stuff, the other comes after and IF
	// the controller says "oh this should have been authenticated" it performs a redirect and stuff
	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param callable|null $next
	 *
	 * @return ResponseInterface
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		$path = $request->getUri()->getPath();
		if($path === '/auth') {
			return $this->handleAuthResponse($request, $response, $next);
		} elseif($path === '/logout') {
			return $this->terminate($request, $response, $next);
		} else {
			return $this->authenticate($request, $response, $next);
		}
	}

	private static function oidc() {
		$oidc = new OpenIDConnectClient(TARALLO_OIDC_ISSUER, TARALLO_OIDC_CLIENT_KEY, TARALLO_OIDC_CLIENT_SECRET);
		$oidc->addScope(['openid', 'profile']);
		return $oidc;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param callable|null $next
	 *
	 * @return ResponseInterface
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	private function authenticate(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		$cookie = $request->getCookieParams();
		/** @var Database $db */
		$db = $request->getAttribute('Database');

		if(isset($cookie[self::COOKIE_NAME])) {
			$id = $cookie[self::COOKIE_NAME];

			$user = $db->userDAO()->getSession($id);
			if($user === null) {
				// No session here... We need to authenticate
				$needToAuthenticate = true;
			} elseif(time() < $user->idTokenExpiry) {
				// We're good to go, the sessions is valid
				$needToAuthenticate = false;
				$request = $request->withAttribute('User', $user);
			} elseif(time() < $user->refreshTokenExpiry) {
				// Ok, ID Token expired, but Refresh Token is still valid
				// TODO: perform refresh HERE
				// Until this is implemented, discard the refresh token and begin a new session
				$needToAuthenticate = true;
			} else {
				// Everything expired, delete the old session and begin a new one
				$needToAuthenticate = true;
				$db->userDAO()->deleteSession($id);
				$id = self::newUniqueIdentifier($db);
			}
		} else {
			// No cookie, cold start
			$needToAuthenticate = true;
			$id = self::newUniqueIdentifier($db);
		}

		if($needToAuthenticate) {
			// Enough time for a login...
			self::setCookie($id, time() + 3600);

			// Delete previous data, ensure that session exists, lock the database row, all in one step
			$db->userDAO()->setDataForSession($id, null);

			// After login, go back there
			$db->userDAO()->setRedirectForSession($id, $request->getUri());

			// Done, see you at /auth!
			$oidc = self::oidc();
			// TODO: port is missing
			$gohere = 'http://' . $request->getUri()->getHost() . ':' . $request->getUri()->getPort() . '/auth';
			if(TARALLO_DEVELOPMENT_ENVIRONMENT) {
				error_log('DEV: Bypassing authentication step 1');

				http_response_code(303);
				header("Location: $gohere");
			} else {
				$oidc->setRedirectURL($gohere);
				$oidc->authenticate();
			}
			exit;
		}

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param callable|null $next
	 *
	 * @return ResponseInterface
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	private function handleAuthResponse(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		// Coming back from a trip to the SSO...

		// ...or is it? Let's check
		$cookie = $request->getCookieParams();
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		if(isset($cookie[self::COOKIE_NAME])) {
			$id = $cookie[self::COOKIE_NAME];
			$redirect = $db->userDAO()->getRedirect($id);

			// TODO: also check the SSO query parameters
			if($redirect === null) {
				// Nowhere to go, probably something is missing
				$request = $request->withAttribute('User', null);
			} else {
				// We have everything! Probably!
				if(TARALLO_DEVELOPMENT_ENVIRONMENT) {
					error_log('DEV: Bypassing authentication step 2');

					$user = new UserSSO();
					$user->uid = 'dev.user';
					$user->cn = 'Developement User';
					$user->idToken = 'F00B4R';
					$user->idTokenExpiry = time() + 60 * 60 * 24;
					$user->refreshToken = 'N0REFRESH';
					$user->refreshTokenExpiry = 0;
				} else {
					$oidc = self::oidc();
					$oidc->authenticate();

					$user = new UserSSO();
					$user->uid = $oidc->getVerifiedClaims('preferred_username');
					$user->cn = $oidc->getVerifiedClaims('name');
					// $user->groups = $oidc->getVerifiedClaims('groups');
					$user->idToken = $oidc->getIdToken();
					$user->idTokenExpiry = $oidc->getVerifiedClaims('exp');
					$user->refreshToken = $oidc->getRefreshToken();
					$user->refreshTokenExpiry = time() + 3600; // TODO: this is apparently not stated in the token, use a constant
				}


				// Store it!
				$db->userDAO()->setDataForSession($id, $user);
				$db->userDAO()->setRedirectForSession($user);
				$request = $request->withAttribute('User', $user);

				$response
					->withStatus(303)
					->withHeader('Location', $redirect); // It's already urlencoded

				// TODO: stop here or handle somewhere?
				return $response;
			}
		} else {
			// uh, cookie is missing... no user here
			$request = $request->withAttribute('User', null);
		}

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}

	private function terminate(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		// TODO: perform SLO

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}
}
