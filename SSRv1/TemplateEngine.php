<?php

namespace WEEEOpen\Tarallo\SSRv1;


use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WEEEOpen\Tarallo\Server\HTTP\Middleware;

class TemplateEngine implements Middleware {
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response,
		?callable $next = null): ResponseInterface {
		// Change this path if you move this file elsewhere!
		$engine = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		$engine->addFolder('stats', $engine->getDirectory() . DIRECTORY_SEPARATOR . 'stats');
		$engine->addData(['lang' => $request->getAttribute('language')]);
		//$engine->loadExtension(new URI($request->path));
		$engine->loadExtension(new TemplateUtilities());
		$request = $request->withAttribute('TemplateEngine', $engine);

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}
}
