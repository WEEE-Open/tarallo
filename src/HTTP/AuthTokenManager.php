<?php


namespace WEEEOpen\Tarallo\HTTP;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\UserLocal;

class AuthTokenManager implements MiddlewareInterface {
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$user = null;
		$auth = $request->getHeader('Authorization');
		if(count($auth) <= 0) {
			$user = null;
		} else {
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
		}
		return $handler->handle($request->withAttribute('User', $user));
	}
}
