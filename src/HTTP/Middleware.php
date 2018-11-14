<?php

namespace WEEEOpen\Tarallo\Server\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Middleware {
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response,
		?callable $next = null): ResponseInterface;

	/*
	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		// Do stuff

		if ($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}
	 */
}
