<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\User;

abstract class AbstractQuery {
	abstract public function __construct($string);

	/**
	 * Run query, acquire results
	 *
	 * @param User|null $user Current user (null if logging in)
	 * @param Database $db Current database (there's also only 1 database, so...)
	 *
	 * @return \JsonSerializable
	 */
	abstract public function run($user, Database $db);

	public static final function factory($method, $path, $postJSON) {
		if($method === 'GET') {
			if($path === '/Features') {
				return new FeatureListQuery('');
			} else if($path === '/Session') {
				return new RefreshQuery('');
			} else {
				return new GetQuery($path);
			}
		} else if($method === 'POST') {
			if($path === null || $path === '') {
				throw new InvalidParameterException('Missing JSON body in POST request'); // TODO: this is the wrong message
			} else if($path === '/Edit') {
				// TODO: more robust handling of "path"
				return new EditQuery($postJSON);
			} else if($path === '/Session') {
				return new LoginQuery($postJSON);
			} else {
				throw new InvalidParameterException('Unknown post request type: ' . $path);
			}
		} else {
			throw new InvalidParameterException('Unsupported HTTP method: ' . $method);
		}
	}
}