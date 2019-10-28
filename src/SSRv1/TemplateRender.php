<?php


namespace WEEEOpen\Tarallo\SSRv1;


use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;

class TemplateRender implements RequestHandlerInterface {
	public function handle(ServerRequestInterface $request): ResponseInterface {
		$template = $request->getAttribute('Template', null);
		$status = $request->getAttribute('ResponseStatus', 200);
		$headers = $request->getAttribute('ResponseHeaders', []);

		if($request->getMethod() === 'HEAD' || $template === null) {
			return new EmptyResponse($status, $headers);
		} else {
			/** @var Engine $engine */
			$engine = $request->getAttribute('TemplateEngine');
			$parameters = $request->getAttribute('TemplateParameters', []);

			return new HtmlResponse($engine->render($template, $parameters), $status, $headers);
		}
	}
}
