<?php


namespace WEEEOpen\Tarallo;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\TextResponse;

class ExceptionHandler implements MiddlewareInterface {
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		try {
			return $handler->handle($request);
		} catch(\Throwable $exception) {
			if(TARALLO_DEVELOPMENT_ENVIRONMENT) {
				return new TextResponse(
					"⚠️ Error ⚠️\n\n" . get_class($exception) . ': ' . $exception->getMessage() . "\n\nStack trace:\n" .
					$exception->getTraceAsString(), 500
				);
			} else {
				return new TextResponse("⚠️ Error ⚠️\n\n" . get_class($exception), 500);
			}
		}

	}
}
