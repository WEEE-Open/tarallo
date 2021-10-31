<?php

namespace WEEEOpen\Tarallo\HTTP;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\User;

class TransactionWrapper implements MiddlewareInterface
{

	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		/** @var Database $db */
		$db = $request->getAttribute('Database');
		/** @var User|null */
		$user = $request->getAttribute('User');
		$db->beginTransaction();
		if ($user !== null) {
			$db->sessionDAO()->setAuditUsername($user->uid);
		}

		try {
			$response = $handler->handle($request);
			$db->commit();
		} catch (\Throwable $e) {
			$db->rollback();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		return $response;
	}
}
