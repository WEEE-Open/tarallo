<?php


namespace WEEEOpen\Tarallo\HTTP;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\User;

class AuthValidator implements MiddlewareInterface {
	private $level;
	const AUTH_LEVEL_ADMIN = 0;
	const AUTH_LEVEL_RW = 2;
	const AUTH_LEVEL_RO = 3;

	/**
	 * Which level of authorization do users need to access this page, as a minimum?
	 *
	 * - 0: admin
	 * - 1: unused
	 * - 2: read and write
	 * - 3: read only
	 *
	 * @param int $level
	 */
	public function __construct(int $level) {
		$this->level = $level;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		/** @var User $user */
		$user = $request->getAttribute('User');

		if(!($user instanceof User)) {
			throw new AuthenticationException();
		}

		if($user->getLevel() > $this->level) {
			throw new AuthorizationException();
		}

		return $handler->handle($request);
	}
}
