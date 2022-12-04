<?php

namespace WEEEOpen\Tarallo\APIv2;

use WEEEOpen\Tarallo\HTTP\InvalidRequestBodyException;
use WEEEOpen\Tarallo\Search;
use WEEEOpen\Tarallo\SearchException;

class SearchBuilder
{
	/**
	 * Build a Search, return it.
	 *
	 * @param array $input Decoded JSON from the client
	 *
	 * @return Search
	 */
	public static function ofArray(array $input): Search
	{
		try {
			return Search::fromJson($input);
		} catch (SearchException $e) {
			throw new InvalidRequestBodyException($e->getMessage(), 0, $e);
		}
	}
}
