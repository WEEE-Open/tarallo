<?php

namespace WEEEOpen\Tarallo\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\DatabaseException;
use WEEEOpen\Tarallo\Session;

class DatabaseConnection implements Middleware {
	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		try {
			$db = new Database(TARALLO_DB_USER, TARALLO_DB_PASS, TARALLO_DB_DSN);
		} catch(DatabaseException $e) {
			throw new DatabaseException('Cannot connect to database');
		}

		try {
			$db->beginTransaction();
			// TODO: SSO or example thing, or basic auth for coso
			// $user = Session::restore($db);
			$db->commit();
		} catch(\Exception $e) {
			$db->rollback();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		if($next) {
			$request = $request->withAttribute('Database', $db)->withAttribute('User', $user);
			return $next($request, $response);
		} else {
			return $response;
		}
	}
}
