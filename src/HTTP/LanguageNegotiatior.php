<?php

namespace WEEEOpen\Tarallo\HTTP;

use Negotiation\LanguageNegotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// This isn't placed in SSRv1 because may be useful for APIs too, e.g. to distribute the giant features json with
// machine-readable and human-readable names according to locale. But that might be part of the SSR despite being an
// API, still undecided on that point.
class LanguageNegotiatior implements Middleware {
	public const en_US = 'en-US';
	public const it_IT = 'it-IT';

	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		$best = self::negotiateLanguage($request->getHeaderLine('Accept-Language'));
		$request = $request->withAttribute('language', $best);

		if($next) {
			return $next($request, $response);
		} else {
			return $response;
		}
	}

	/**
	 * Get best language among supported ones.
	 *
	 * @param string|null $languages Contents of the Accept-Language header
	 *
	 * @return string One of the class constants representing the chosen language
	 */
	private static function negotiateLanguage(string $languages = null): string {
		$supported = [self::en_US, self::it_IT];

		if($languages === null || $languages === '') {
			return $supported[0];
		}

		$negotiator = new LanguageNegotiator();
		$bestLanguage = $negotiator->getBest($languages, $supported);

		if($bestLanguage === null) {
			return $supported[0];
		} else {
			/** @noinspection PhpUndefinedMethodInspection */
			return $bestLanguage->getType();
		}
	}

}
