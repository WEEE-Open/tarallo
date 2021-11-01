<?php

namespace WEEEOpen\Tarallo\HTTP;

use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\OpenIDConnectRefreshClient;
use WEEEOpen\Tarallo\SessionSSO;
use WEEEOpen\Tarallo\UserSSO;
use Laminas\Diactoros\Response\RedirectResponse;

class AuthManager implements MiddlewareInterface
{
	private const COOKIE_NAME = 'tsessionsso';
	private const KEYSPACE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
	private const KEYSPACE_STRLEN = 64;
	private $browser = true;

	/**
	 * @param bool $browser The client is a browser that can be redirect to the SSO server
	 */
	public function __construct(bool $browser = true)
	{
		$this->browser = $browser;
	}

	/**
	 * Set the cookie
	 *
	 * @param string $newContent
	 * @param int $expire
	 */
	protected static function setCookie(string $newContent, int $expire)
	{
		setcookie(self::COOKIE_NAME, $newContent, $expire, '', '', !TARALLO_DEVELOPMENT_ENVIRONMENT, true);
	}

	private static function oidc(): OpenIDConnectClient
	{
		$oidc = new OpenIDConnectClient(TARALLO_OIDC_ISSUER, TARALLO_OIDC_CLIENT_KEY, TARALLO_OIDC_CLIENT_SECRET);
		$oidc->addScope(['openid', 'profile']);
		return $oidc;
	}

	/**
	 * Create a new session identifier and check that it's unique.
	 * ...it will probably be, but check anyway.
	 *
	 * @param Database $db
	 *
	 * @return string
	 */
	private static function newUniqueIdentifier(Database $db): string
	{
		do {
			$id = self::newIdentifier();
		} while ($db->sessionDAO()->sessionExists($id));

		return $id;
	}

	/**
	 * Create a new session identifier.
	 *
	 * @return string
	 * @see newUniqueIdentifier
	 */
	private static function newIdentifier(): string
	{
		$str = '';
		for ($i = 0; $i < 32; $i++) {
			try {
				$str .= self::KEYSPACE[random_int(0, self::KEYSPACE_STRLEN - 1)];
			} catch (\Exception $e) {
				// Okay PHPStorm, will you stop complaining now? Please?
				echo 'Not enough entropy';
				exit(1);
			}
		}

		return $str;
	}

	/**
	 * Is the request within the grace time limits, if ID token has expired?
	 *
	 * @param ServerRequestInterface $request The request
	 * @param int $expiry ID token expiry
	 *
	 * @return bool True if it should be graced, false if it shouldn't
	 */
	private static function withinGrace(ServerRequestInterface $request, int $expiry): bool
	{
		if (time() + TARALLO_POST_GRACE_TIME < $expiry) {
			return false;
		}
		$method = $request->getMethod();
		if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH') {
			return false;
		}
		return true;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// User is already set (by another authentication handler), skip ahead
		if ($request->getAttribute('User', null) !== null) {
			return $handler->handle($request);
		}

		$path = $request->getUri()->getPath();
		// These paths are in the SSR thing
		if ($path === '/auth' && $this->browser) {
			return $this->handleAuthResponse($request, $handler);
		} elseif ($path === '/logout' && $this->browser) {
			return $this->terminate($request, $handler);
		}

		$cookie = $request->getCookieParams();
		/** @var Database $db */
		$db = $request->getAttribute('Database');

		if (isset($cookie[self::COOKIE_NAME])) {
			$id = $cookie[self::COOKIE_NAME];
			$session = $db->sessionDAO()->getSession($id);

			if ($session === null) {
				// Failed login or very old expired session or some kind of attack, delete the cookie
				self::setCookie($id, 1);
				$db->beginTransaction();
				$db->sessionDAO()->deleteSession($id);
				$db->commit();

				$session = null;
				$user = null;
			} elseif (time() < $session->idTokenExpiry || self::withinGrace($request, $session->idTokenExpiry)) {
				// We're good to go, the sessions is valid (or within grace time)
				$user = UserSSO::fromSession($session);
			} elseif (time() < $session->refreshTokenExpiry) {
				// Ok, ID Token expired, but Refresh Token is still valid

				$refreshedSession = $this->performRefresh($session);

				if ($refreshedSession === null) {
					// Refresh failed, discard all tokens and begin a new session
					self::setCookie($id, 1);
					$db->beginTransaction();
					$db->sessionDAO()->deleteSession($id);
					$db->commit();

					// Everything is gone
					$session = null;
					$user = null;
				} else {
					// Done, refresh happened, we have an updated session
					$db->beginTransaction();
					$db->sessionDAO()->setDataForSession($id, $refreshedSession);
					$db->commit();

					// Here we go
					$session = $refreshedSession;
					$user = UserSSO::fromSession($refreshedSession);
				}
			} else {
				// Everything expired, delete the old session and begin a new one
				self::setCookie($id, 1);
				$db->beginTransaction();
				$db->sessionDAO()->deleteSession($id);
				$db->commit();

				// Right now, we have nothing
				$session = null;
				$user = null;
			}
		} else {
			// No cookie
			$session = null;
			$user = null;
		}

		try {
			$response = $handler->handle($request->withAttribute('User', $user));
		} catch (AuthenticationException $e) {
			//error_log('Caught AuthenticationException');
			if (!$this->browser) {
				throw $e;
			}
			// We need to authenticate.

			// Create new session
			$id = self::newUniqueIdentifier($db);

			$db->beginTransaction();
			// Delete previous data (if any), ensure that session exists, lock the database row (useless)
			$db->sessionDAO()->setDataForSession($id, null);
			// After login, go back there
			//error_log('Set redirect to ' . $request->getUri());
			$db->sessionDAO()->setRedirectForSession($id, $request->getUri());
			$db->commit();

			// Expire at the end of browser session, the SSO page may remain open for hours anyway
			self::setCookie($id, 0);

			// Done, see you at /auth!
			if (TARALLO_DEVELOPMENT_ENVIRONMENT) {
				//error_log('DEV: Bypassing authentication step 1');

				http_response_code(303);
				header("Location: /auth?code=bogus_for_auth_bypass");
			} else {
				$oidc = self::oidc();
				$gohere = 'https://' . $request->getUri()->getHost() . '/auth';

				$oidc->setRedirectURL($gohere);
				$oidc->authenticate();
			}
			exit;
		}
		return $response;
	}

	private function handleAuthResponse(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler
	): ResponseInterface {
		// Coming back from a trip to the SSO...

		// ...or is it? Let's check
		$cookie = $request->getCookieParams();
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		if (isset($cookie[self::COOKIE_NAME]) && (isset($_REQUEST["code"]) || isset($_REQUEST["error"]))) {
			$id = $cookie[self::COOKIE_NAME];
			$redirect = $db->sessionDAO()->getRedirect($id);

			if ($redirect === null) {
				// Nowhere to go, probably something is missing
				$request = $request->withAttribute('User', null);
			} else {
				// We have everything! Probably!
				if (TARALLO_DEVELOPMENT_ENVIRONMENT) {
					//error_log('DEV: Bypassing authentication step 2');

					$session = new SessionSSO();
					$session->uid = 'dev.user';
					$session->cn = 'Developement User';
					$session->idToken = 'F00B4R';
					$session->idTokenValidityTime = 60 * 60 * 24;
					$session->idTokenExpiry = time() + $session->idTokenValidityTime;
					$session->groups = ['Admin', 'Example', 'Developers'];
					$session->refreshToken = 'N0REFRESH';
					$session->refreshTokenValidityTime = 0;
					$session->refreshTokenExpiry = 0;
				} else {
					$oidc = self::oidc();
					$oidc->authenticate();

					$session = new SessionSSO();
					$session->uid = $oidc->getVerifiedClaims('preferred_username');
					$session->cn = $oidc->getVerifiedClaims('name');
					$session->groups = $oidc->getVerifiedClaims('groups') ?? [];
					$session->idToken = $oidc->getIdToken();
					$session->idTokenExpiry = $oidc->getVerifiedClaims('exp');
					$session->idTokenValidityTime = $session->idTokenExpiry - time();
					if ($oidc->getRefreshToken() === null) {
						$session->refreshToken = null;
						$session->refreshTokenExpiry = 0;
						$session->refreshTokenValidityTime = 0;
					} else {
						$session->refreshToken = $oidc->getRefreshToken();
						$session->refreshTokenExpiry = time() + TARALLO_OIDC_REFRESH_TOKEN_EXPIRY;
						$session->refreshTokenValidityTime = TARALLO_OIDC_REFRESH_TOKEN_EXPIRY;
					}
				}

				// Update the cookie
				self::setCookie($id, max($session->idTokenExpiry, $session->refreshTokenExpiry));

				// Store it!
				//error_log('"Store it!"');
				$db->beginTransaction();
				$db->sessionDAO()->setDataForSession($id, $session);
				//error_log('Set redirect to ' . $request->getUri());
				$db->sessionDAO()->setRedirectForSession($id, null);
				$db->commit();
				//$request = $request->withAttribute('User', UserSSO::fromSession($session));

				// Do not process further middleware, just redirect
				return new RedirectResponse($redirect, 303);
			}
		} else {
			// uh, cookie is missing... no user here
			$request = $request->withAttribute('User', null);
		}

		return $handler->handle($request);
	}

	private function terminate(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler
	): ResponseInterface {
		$cookie = $request->getCookieParams();

		// Logout done. Really really done. Just render the logout page!
		if (isset($request->getQueryParams()['done']) && !isset($cookie[self::COOKIE_NAME])) {
			return $handler->handle($request);
		}

		// Or let's logout and redirect!
		if (isset($cookie[self::COOKIE_NAME])) {
			// Get session data
			$id = $cookie[self::COOKIE_NAME];
			/** @var Database $db */
			$db = $request->getAttribute('Database');
			$data = $db->sessionDAO()->getSession($id);

			// Destroy the local session
			$db->beginTransaction();
			$db->sessionDAO()->deleteSession($id);
			self::setCookie($id, 1);
			$db->commit();

			$token = $data->idToken;
		} else {
			$token = null;
		}

		if (TARALLO_DEVELOPMENT_ENVIRONMENT) {
			//error_log('DEV: Bypassing logout');
			return new RedirectResponse($request->getUri()->withQuery('done=true'), 302);
		} else {
			$oidc = self::oidc();
			$oidc->signOut($token, (string) $request->getUri()->withQuery('done=true'));
			exit;
		}
	}

	private function performRefresh(SessionSSO $previousSession): ?SessionSSO
	{
		$oidc = new OpenIDConnectRefreshClient(TARALLO_OIDC_ISSUER, TARALLO_OIDC_CLIENT_KEY, TARALLO_OIDC_CLIENT_SECRET);
		$oidc->addScope(['openid', 'profile']);
		$json = $oidc->refreshToken($previousSession->refreshToken);

		// The "correct way" is apparently to call the refresh token endpoint ad obtain a new refresh token + access
		// token. Which is never converted to an ID token, see e.g. https://bitbucket.org/moodle/moodle/src/e04a73ccc06e18d8d3b3661f8f9bc16911747830/lib/classes/oauth2/api.php?at=master#api.php-473
		// And to error out if the server replies with an error. How long will the refreshed session be valid? Who
		// knows, the application choses, as long as the refresh token does not expire in the meantime (the refresh
		// token has no expiration date anyway, but the server can decide to delete it and reject further requests
		// made with that token).
		//
		// Some alternatives exist: https://stackoverflow.com/a/47363175
		// But none of them is as seamless as obtaining a new ID token in the refresh process: no redirects, no user
		// interaction, the new ID token has a defined expiration date chosen by the server...
		// What can we do?
		//
		// Let's see if refresh happened, first of all...
		if (isset($json->error)) {
			error_log('Error while refreshing: ' . $json->error);
			return null;
		}
		// Do we have a new ID token?
		if (!isset($json->id_token)) {
			error_log('SSO server did not provide an ID token after refresh');
			return null;
		}
		// We also need an access token
		if (!isset($json->access_token)) {
			error_log('SSO server did not provide an access token after refresh');
			return null;
		}

		try {
			// Validate the ID token signature
			$valid = $oidc->verifyJWTsignature($json->id_token);
			if (!$valid) {
				return null;
			}
		} catch (OpenIDConnectClientException $e) {
			return null;
		}

		// Now decode the claims
		// decodeJWT() does exactly this
		$claims = json_decode(\Jumbojett\base64url_decode(explode(".", $json->id_token)[1]));

		try {
			// There's a comparison in verifyJWTclaims for the nonce set by the OIDC client. Which does not set any nonce.
			// This prevents replay attacks during redirects, but this a backend request, it's simply not possible to do
			// such an attack without taking control of the SSO server domain and generating a valid TLS certificate,
			// at which point the attacker has basically control of everything...
			// So we set it to the received value and the check passes.
			$oidc->setNonceForRefresh($claims->nonce);

			// We need to validate the claims. Possibly. Not entirely sure if this part is required.
			$method = new ReflectionMethod($oidc, 'verifyJWTclaims');
			$method->setAccessible(true);
			$valid = $method->invoke($oidc, $claims, $json->access_token);
			if (!$valid) {
				error_log('verifyJWTclaims failed');
				return null;
			}
		} catch (\ReflectionException $e) {
			return null;
		} /** @noinspection PhpRedundantCatchClauseInspection */ catch (OpenIDConnectClientException $e) {
			error_log('JWT claims validation failed: ' . $e->getMessage());
			return null;
		}

		$session = new SessionSSO();
		$now = time();

		$claims = (array) $claims;

		// Done! We just need to fill the new session with the claims we just obtained from the ID token!
		$session->uid = $claims['preferred_username'] ?? null;
		$session->cn = $claims['name'] ?? null;
		$session->groups = $claims['groups'] ?? [];
		$session->idToken = $json->id_token;
		// Guess an expiry time, if not available
		$session->idTokenExpiry = $claims['exp'] ?? $now + $session->idTokenValidityTime;
		$session->idTokenValidityTime = $session->idTokenExpiry - $now;
		if ($oidc->getRefreshToken() === null) {
			// No new refresh token? We'll try the old one next time, the worst that could happen is that the server
			// rejects it
			$session->refreshToken = $previousSession->refreshToken;
		} else {
			// Do we have a new refresh token?
			$session->refreshToken = $oidc->getRefreshToken();
		}
		// Assume these didn't change
		$session->refreshTokenExpiry = $now + $previousSession->refreshTokenValidityTime;
		$session->refreshTokenValidityTime = $previousSession->refreshTokenValidityTime;
		return $session;
	}
}
