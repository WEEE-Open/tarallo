<?php

namespace WEEEOpen\Tarallo\Server\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WEEEOpen\Tarallo\Server\Database\Database;
use WEEEOpen\Tarallo\Server\Database\DatabaseException;
use WEEEOpen\Tarallo\Server\Session;

class DatabaseConnection implements Middleware {
//	public const en_US = 'en-US';
//	public const it_IT = 'it-IT';

	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		?callable $next = null
	): ResponseInterface {
		try {
			$db = new Database(DB_USERNAME, DB_PASSWORD, DB_DSN);
		} catch(DatabaseException $e) {
			throw new DatabaseException('Cannot connect to database');
		}

		try {
			$db->beginTransaction();
			$user = Session::restore($db);
			$db->commit();
		} catch(\Exception $e) {
			$db->rollback();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		if($next) {
			$request = $request->withAttribute('Database', $db)->withAttribute('User', $user);
			return $next($request, $response);
		} else {
			return $response;
		}
	}

//	/**
//	 * Get best language among supported ones.
//	 *
//	 * @param string|null $languages Contents of the Accept-Language header
//	 *
//	 * @return string One of the class constants representing the chosen language
//	 */
//	private static function negotiateLanguage(string $languages = null): string {
//		$supported = [self::en_US, self::it_IT];
//
//		if($languages === null || $languages === '') {
//			return $supported[0];
//		}
//
//		$negotiator = new LanguageNegotiator();
//		$bestLanguage = $negotiator->getBest($languages, $supported);
//
//		if($bestLanguage === null) {
//			return $supported[0];
//		} else {
//			/** @noinspection PhpUndefinedMethodInspection */
//			return $bestLanguage->getType();
//		}
//	}

}
