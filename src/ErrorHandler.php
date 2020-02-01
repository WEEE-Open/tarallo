<?php


namespace WEEEOpen\Tarallo;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\TextResponse;

class ErrorHandler implements MiddlewareInterface {
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		try {
			return $handler->handle($request);
		} catch(\Throwable $exception) {
			$short = "⚠️ Error ⚠️\n\n" . get_class($exception);
			$full = $short . ': ' . $exception->getMessage() . ' in ' .
				$exception->getFile() . ' on line ' . $exception->getLine() . "\n\nStack trace:\n" .
				$exception->getTraceAsString();

			if(TARALLO_DEVELOPMENT_ENVIRONMENT) {
				return new TextResponse($full, 500);
			} else {
				error_log($full);
				return new TextResponse($short, 500);
			}
		}

	}
}
