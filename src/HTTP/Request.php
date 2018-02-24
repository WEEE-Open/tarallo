<?php

namespace WEEEOpen\Tarallo\Server\HTTP;


use Negotiation\LanguageNegotiator;
use Negotiation\Negotiator;

class Request {
	public $host;
	public $method;
	public $path;
	public $responseType;
	public $requestType;
	public $language;
	public $querystring;
	public $payload;

	public function __construct(
		string $host,
		string $method,
		string $path,
		string $accept,
		string $languages = null,
		array $querystring = null,
		string $contentType = null,
		string $payload = null
	) {
		$responseType = self::negotiateType($accept);
		unset($accept);
		$language = self::negotiateLanguage($languages);
		unset($languages);

		$this->host = $host;
		$this->method = $method;
		$this->path = $path;
		$this->responseType = $responseType;
		$this->requestType = $contentType;
		$this->language = $language;
		$this->querystring = $querystring;
		$this->payload = $payload;
	}

	public static function ofGlobals() {
		if(isset($_SERVER['PATH_INFO'])) {
			$path = urldecode($_SERVER['PATH_INFO']);
		} else if(!isset($_SERVER['REQUEST_URI'])) {
			// Direct request to index.php or whatever
			$path = '';
		} else {
			throw new \LogicException('Cannot find PATH_INFO');
		}

		if(isset($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $querystring);
			if(empty($querystring)) {
				$querystring = null;
			}
		} else {
			$querystring = null;
		}

		if(isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = self::reverseNegotiateContent($_SERVER['CONTENT_TYPE']);
			$rawcontents = file_get_contents("php://input");
		} else {
			// GET request
			$contentType = null;
			$rawcontents = null;
		}

		return new self($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_METHOD'], $path, $_SERVER['HTTP_ACCEPT'] ?? '',
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null, $querystring, $contentType, $rawcontents);
	}

	private static function negotiateType(string $accept): string {
		$negotiator = new Negotiator();
		$acceptDefaults = [
			'application/json; charset=UTF-8',
			'application/json',
			'text/html; charset=UTF-8',
			'text/html'
		];
		$mediaType = $negotiator->getBest($accept, $acceptDefaults);
		if($mediaType === null) {
			throw new \RuntimeException('Cannot negotiate content: none is acceptable');
		} else {
			// Taken straight from the example: undefined method. But it's there, so ignore the warning.
			/** @noinspection PhpUndefinedMethodInspection */
			return $mediaType->getType();
		}
	}

	private static function negotiateLanguage(string $languages = null): string {
		$supported = ['en', 'it'];

		if($languages === null) {
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

	private static function reverseNegotiateContent(string $content): string {
		$negotiator = new Negotiator();
		$supported = 'application/json; text/html;';
		$contentType = $negotiator->getBest($supported, [$content]);

		if($contentType === null) {
			throw new \RuntimeException('Content type ' . $content . ' not supported');
		} else {
			// Taken straight from the example: undefined method. But it's there, so ignore the warning.
			/** @noinspection PhpUndefinedMethodInspection */
			return $contentType->getType();
		}
	}
}
