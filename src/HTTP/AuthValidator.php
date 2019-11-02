<?php


namespace WEEEOpen\Tarallo\HTTP;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\User;

class AuthValidator implements MiddlewareInterface {
	private $level;

	/**
	 * Which level of authorization do users need to access this page, as a minimum?
	 *
	 * @param int $level
	 * @see User for level constants
	 */
	public function __construct(int $level) {
		$this->level = $level;
	}

	/**
	 * Check that user has the required permissions (aka authorize)
	 *
	 * @param User $user
	 * @param int $requiredLevel Required access level
	 * @see User for level constants
	 */
	public static function ensureLevel(User $user, int $requiredLevel) {
		if($user->getLevel() > $requiredLevel) {
			throw new AuthorizationException();
		}
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		/** @var User $user */
		$user = $request->getAttribute('User');

		if(!($user instanceof User)) {
			throw new AuthenticationException();
		}

		self::ensureLevel($user, $this->level);

		return $handler->handle($request);
	}
}
