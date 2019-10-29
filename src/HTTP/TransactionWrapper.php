<?php


namespace WEEEOpen\Tarallo\HTTP;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TransactionWrapper implements MiddlewareInterface {

	public function process(Request $request, RequestHandlerInterface $handler): Response {
		$db = $request->getAttribute('Database');
		$db->beginTransaction();

		try {
			$response = $handler->handle($request);
			$db->commit();
		} catch(\Throwable $e) {
			$db->rollback();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		return $response;
	}
}
