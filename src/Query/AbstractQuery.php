<?php
namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

abstract class AbstractQuery {
	abstract public function __construct($string);

	abstract public function run($user, Database $db);

	public static final function factory($method, $path, $postJSON) {
		if($method === 'GET') {
			if($path === '/Features') {
				return new FeatureListQuery('');
			} else {
				return new GetQuery($path);
			}
		} else if($method === 'POST') {
			if($path === null || $path === '') {
				throw new InvalidParameterException('Missing JSON body in POST request');
			} else if($path === '/Edit') {
				// TODO: more robust handling of "path"
				return new EditQuery($postJSON);
				// TODO: throw new \Exception('Authentication needed'); somewhere in there
			} else if($path === '/Login') {
				return new LoginQuery($postJSON);
			} else if($path === '/Refresh') {
				return new RefreshQuery($postJSON);
			} else {
				throw new InvalidParameterException('Unknown post request type: ' . $path);
			}
		} else {
			throw new InvalidParameterException('Unsupported HTTP method: ' . $method);
		}
	}
}