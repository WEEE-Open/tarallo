<?php


namespace WEEEOpen\Tarallo\SSRv1;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WEEEOpen\Tarallo\HTTP\AuthorizationException;
use WEEEOpen\Tarallo\NotFoundException;

class GracefulExceptionHandler implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

		try {
			return $handler->handle($request);
//		} catch(AuthenticationException $e) {
//			$request = $request
//				->withAttribute('Template', 'error')
//				->withAttribute('TemplateParameters', ['reasonNoEscape' => '<a href="/login">Please authenticate</a>'])
//				->withAttribute('ResponseCode', 401)
//				->withAttribute('ResponseHeaders', ['WWW-Authenticate' => 'login']);
		} catch(AuthorizationException $e) {
			$request = $request
				->withAttribute('Template', 'error')
				->withAttribute('ResponseCode', 403);
		} catch(NotFoundException $e) {
			$request = $request
				->withAttribute('Template', 'error')
				->withAttribute('TemplateParameters', ['reason' => 'Whatever you\'re looking for, it doesn\'t exist.'])
				->withAttribute('ResponseCode', 404);
		}

		// Call that middleware directly, do not process others
		// It should have been a RequestHandlerInterface but Relay implements itself RequestHandlerInterface, it only
		// supports MiddlewareInterface classes
		return (new TemplateRender())->process($request, $handler);
	}
}