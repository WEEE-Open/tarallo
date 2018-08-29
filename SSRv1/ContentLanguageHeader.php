<?php

namespace WEEEOpen\Tarallo\Server\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentLanguageHeader implements Middleware {
	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next
	): ResponseInterface {
		$language = $request->getAttribute('language', null);
		if(is_string($language)) {
			$response = $response->withHeader('Content-Language', $request->getAttribute('language'));
		} else {
			throw new \InvalidArgumentException('No language attribute found or not a string');
		}

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}
}
