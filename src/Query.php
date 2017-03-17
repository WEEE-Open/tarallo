<?php
namespace WEEEOpen\Tarallo;


class Query {
	private $built = false;
	private $method;
	private $parseFieldsDouble = [
		'Location' => 'parseLocation',
		'Search'   => 'parseSearch',
		'Sort'     => 'parseSort',
		'Depth'    => 'parseDepth',
		'Parent'   => 'parseParent',
		'Language' => 'parseLanguage',
		'Token'    => 'parseToken',
	];
	private $parseFieldsSingle = [
		'Login' => 'parseLogin',
		'Edit'  => 'parseEdit',
	];

	private $location = null;
	private $search = null;
	private $sort = null;
	private $depth = null;
	private $parent = null;
	private $language = null;
	private $token = null;

	public function fromString($string, $method) {
		if(!is_string($string) || $string === '') {
			throw new \InvalidArgumentException('Query string must be a non-empty string');
		}

		$this->setBuilt();
		$this->setMethod($method);

		$pieces = explode('/', $this->normalizeString($string));
		$this->parsePieces($pieces);

		return $this;
	}

	private function setBuilt() {
		if($this->built) {
			throw new \LogicException('Query object already built');
		}
		$this->built = true;
	}

	private function normalizeString($string) {
		$string = substr($string, 1); // remove first slash
		if(substr($string, - 1) === '/') {
			$string = substr($string, 0, strlen($string) - 1);
		}

		return $string;
	}

	private function setMethod($method) {
		if($method === 'GET' || $method === 'POST') {
			$this->method = $method;
		} else {
			throw new \InvalidArgumentException('Unsupported method ' . $method);
		}
	}

	private function parsePieces($pieces) {
		$i = 0;
		$c = count($pieces);
		while($i < $c) {
			if(isset($this->parseFieldsSingle[ $pieces[ $i ] ])) {
				$fn = $this->getParseCallable($this->parseFieldsSingle[ $pieces[ $i ] ]);
				call_user_func($fn);
				$i ++;
			} else if(isset($this->parseFieldsDouble[ $pieces[ $i ] ])) {
				if($i + 1 < $c) {
					$fn = $this->getParseCallable($this->parseFieldsDouble[ $pieces[ $i ] ]);
					call_user_func($fn, $pieces[ $i + 1 ]);
					$i += 2;
				} else {
					throw new \InvalidArgumentException('Missing parameter for field ' . $pieces[ $i ]);
				}
			} else {
				throw new \InvalidArgumentException('Unknown field ' . $pieces[ $i ]);
			}
		}
	}

	private function getParseCallable($method) {
		$fn = [$this, $method];
		if(!is_callable($fn)) {
			throw new \LogicException('Cannot call ' . $fn[1]);
		}

		return $fn;
	}

}