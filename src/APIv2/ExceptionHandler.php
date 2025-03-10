<?php

namespace WEEEOpen\Tarallo\APIv2;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class ExceptionHandler implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (\Exception $e) {
			$error = ErrorResponse::fromException($e);
			$status = $error->status;
			if ($status === 401) {
				return new JsonResponse($error, $status, ['WWW-Authenticate' => 'Token']);
			} else {
				return new JsonResponse($error, $status);
			}
		}
	}
}
