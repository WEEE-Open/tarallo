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
			} else if($path === '/Session') {
				return new RefreshQuery('');
			} else if($path === '/Logout') { // TODO: this should be a POST request, really...
				return new GetQuery($path);
			}
		} else if($method === 'POST') {
			if($path === null || $path === '') {
				throw new InvalidParameterException('Missing JSON body in POST request'); // TODO: this is the wrong message
			} else if($path === '/Edit') {
				// TODO: more robust handling of "path"
				return new EditQuery($postJSON);
			} else if($path === '/Login') {
				return new LoginQuery($postJSON);
			} else {
				throw new InvalidParameterException('Unknown post request type: ' . $path);
			}
		} else {
			throw new InvalidParameterException('Unsupported HTTP method: ' . $method);
		}
	}
}