<?php


namespace WEEEOpen\Tarallo\APIv2;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\TextResponse;

class EnsureJson implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$method = $request->getMethod();
		if($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
			/** @noinspection PhpUndefinedMethodInspection it exists. */
			if(explode(';', $request->getContentType(), 2)[0] !== 'application/json') {
				return new TextResponse('Request body must contain JSON, check your Content-Type', 415);
			}
		}

		return $handler->handle($request);
	}
}