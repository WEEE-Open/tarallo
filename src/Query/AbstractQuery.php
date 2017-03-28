<?php
namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

abstract class AbstractQuery {
	protected $built = false;

	abstract public function fromString($string);

	protected final function setBuilt() {
		if($this->isBuilt()) {
			throw new \LogicException('Query object already built');
		}
		$this->built = true;
	}

	protected final function isBuilt() {
		return $this->built;
	}

	abstract public function run($user, Database $db);

	public static final function factory() {
		if($_SERVER['REQUEST_METHOD'] === 'GET') {
			return (new GetQuery())->fromString($_REQUEST['path']);
		} else if($_SERVER['REQUEST_METHOD'] === 'POST') {
			$postJSON = file_get_contents('php://input');
			if($_REQUEST['path'] === null || $_REQUEST['path'] === '') {
				throw new InvalidParameterException('Missing JSON body in POST request');
			} else if($_REQUEST['path'] === '/Edit') {
				// TODO: more robust handling of "path"
				return (new EditQuery())->fromString($postJSON);
				// TODO: throw new \Exception('Authentication needed'); somewhere in there
			} else if($_REQUEST['path'] === '/Login') {
				return (new LoginQuery())->fromString($postJSON);
			} else {
				throw new InvalidParameterException('Unknown post request type: ' . $_REQUEST['path']);
			}
		} else {
			throw new InvalidParameterException('Unsupported HTTP method: ' . $_SERVER['REQUEST_METHOD']);
		}
	}
}