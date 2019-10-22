<?php

namespace WEEEOpen\Tarallo\HTTP;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class AbstractController {
	const cachefile = '';

	abstract public static function handleExceptions(
		Request $request,
		Response $response,
		?callable $next = null
	): Response;

	public static function doTransaction(Request $request, Response $response, ?callable $next = null): Response {
		$db = $request->getAttribute('Database');
		$db->beginTransaction();

		try {
			if($next) {
				$response = $next($request, $response);
			}
			$db->commit();
		} catch(\Throwable $e) {
			$db->rollback();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		return $response;
	}

	abstract public static function getDispatcher(string $cachefile): Dispatcher;

	abstract public static function handle(Request $request): Response;

	public static function route(Request $request): array {
		return static::getDispatcher(static::cachefile)->dispatch($request->getMethod(), $request->getUri()->getPath());
	}
}