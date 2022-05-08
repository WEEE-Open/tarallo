<?php

namespace WEEEOpen\Tarallo\SSRv1;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TemplateEngine implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Change this path if you move this file elsewhere!
		$engine = new Engine(__DIR__ . DIRECTORY_SEPARATOR . 'templates');
		$engine->addFolder('stats', $engine->getDirectory() . DIRECTORY_SEPARATOR . 'stats');
		$engine->addFolder('bulk', $engine->getDirectory() . DIRECTORY_SEPARATOR . 'bulk');
		$engine->addFolder('info', $engine->getDirectory() . DIRECTORY_SEPARATOR . 'info');
		$engine->addFolder('options', $engine->getDirectory() . DIRECTORY_SEPARATOR . 'options');
		$engine->addData(
			[
				'lang' => 'en',
				'user' => $request->getAttribute('User'),
				'self' => $request->getUri()->getPath()
			]
		);
		//$engine->addData(['lang' => $request->getAttribute('language')]);
		$engine->loadExtension(new TemplateUtilities());
		$request = $request->withAttribute('TemplateEngine', $engine);

		return $handler->handle($request);
	}
}
