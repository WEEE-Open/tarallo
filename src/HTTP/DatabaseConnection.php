<?php

namespace WEEEOpen\Tarallo\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Database\DatabaseException;

class DatabaseConnection implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			$db = new Database(TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, TARALLO_DB_DSN);
		} catch (DatabaseException $e) {
			throw new DatabaseException('Cannot connect to database');
		}

		$request = $request->withAttribute('Database', $db);
		return $handler->handle($request);
	}
}
