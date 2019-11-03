<?php /** @noinspection PhpComposerExtensionStubsInspection APCu is suggested, but that's not enough for PHPStorm */


namespace WEEEOpen\Tarallo\HTTP;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\UserLocal;

class AuthTokenManager implements MiddlewareInterface {
	private $useApcu;

	public function __construct() {
		$this->useApcu = !TARALLO_DEVELOPMENT_ENVIRONMENT && extension_loaded('apcu') && boolval(ini_get('apc.enabled'));
	}

	private function useApcu(): bool {
		return $this->useApcu;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$user = null;
		$auth = $request->getHeader('Authorization');
		if(count($auth) > 0) {
			if(count($auth) >= 2) {
				throw new AuthorizationException('Too many Authorization header values, found ' . count($auth));
			}
			$parts = explode(' ', $auth[0], 2);
			if(count($parts) <= 1 || $parts[0] !== 'Token') {
				throw new AuthorizationException('Malformed Authorization header, expected "Token f00B4r..."');
			}
			$token = $parts[1];

			/** @var Database $db */
			$db = $request->getAttribute('Database');

			// Try to read from cache. Yes it stores the plaintext "password", but at least avoids the overhead of
			// password_verify on each request, that's the point. Cache entries have a limited TTL anyway...
			if($this->useApcu()) {
				$user = apcu_fetch('token-' . $token, $cached);
				if($cached) {
					return $handler->handle($request->withAttribute('User', $user));
				}
			}

			// Cache miss :(
			$session = $db->sessionDAO()->getToken($token, $lastAccess);
			if($session === null || $lastAccess === null) {
				$user = null;
			} else {
				$user = UserLocal::fromSession($session);
				/** @var \DateTimeImmutable $lastAccess */
				try {
					$now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Rome'));
					if($now->format('Y-m-d') !== $lastAccess->format('Y-m-d')) {
						$db->beginTransaction();
						$db->sessionDAO()->bumpToken($token);
						$db->commit();
					}
				} catch(\Exception $e) {
					$user = null;
				}
			}

			if($this->useApcu()) {
				if($user === null) {
					// Don't fill the cache with useless entries, users should notice pretty soon if the entered and
					// invalid token
					$ttl = 60; // 1 minute
				} else {
					$ttl = 30 * 60; // 30 minutes
				}
				apcu_store('token-' . $token, $user, $ttl);
			}
		}
		return $handler->handle($request->withAttribute('User', $user));
	}
}
