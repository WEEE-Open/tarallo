<?php

namespace WEEEOpen\Tarallo\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\DatabaseException;

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

		if($next) {
			$request = $request->withAttribute('Database', $db);
			return $next($request, $response);
		} else {
			return $response;
		}
	}
}
