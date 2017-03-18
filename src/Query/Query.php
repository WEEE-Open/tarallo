<?php
namespace WEEEOpen\Tarallo\Query;


class Query {
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const FIELD_LOCATION = 'Location';
	const FIELD_SEARCH = 'Search';
	const FIELD_SORT = 'Sort';
	const FIELD_DEPTH = 'Depth';
	const FIELD_PARENT = 'Parent';
	const FIELD_LANGUAGE = 'Language';
	const FIELD_TOKEN = 'Token';

	private $built = false;
	private $method;
	/** @var $parseFieldsDouble QueryField[] */
	private $parseFieldsDouble;
	private $parseFieldsSingle;

	function __construct() {
		$this->parseFieldsDouble = [
			self::FIELD_LOCATION => new QueryFieldLocation(),
			self::FIELD_SEARCH   => new QueryFieldSearch(),
			self::FIELD_SORT     => new QueryFieldSort(),
			self::FIELD_DEPTH    => new QueryFieldDepth(),
			self::FIELD_PARENT   => new QueryFieldParent(),
			self::FIELD_LANGUAGE => new QueryFieldLanguage(),
			self::FIELD_TOKEN    => new QueryFieldToken(),
		];
		$this->parseFieldsSingle = [
			'Login' => 'parseLogin',
			'Edit'  => 'parseEdit',
		];
	}

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
		if($method === self::METHOD_GET || $method === self::METHOD_POST) {
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
				if($this->method !== self::METHOD_POST) {
					throw new \InvalidArgumentException('Field ' . $pieces[ $i ] . ' not allowed for method ' . $this->method);
				}
				$fn = $this->getParseCallable($this->parseFieldsSingle[ $pieces[ $i ] ]);
				call_user_func($fn);
				$i ++;
			} else if(isset($this->parseFieldsDouble[ $pieces[ $i ] ])) {
				if($this->method !== self::METHOD_GET) {
					throw new \InvalidArgumentException('Field ' . $pieces[ $i ] . ' not allowed for method ' . $this->method);
				}
				if($i + 1 < $c) {
					$this->parseFieldsDouble[ $pieces[ $i ] ]->parse($pieces[ $i + 1 ]);
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

	private function parseLogin($parameter) {
		// TODO: implement
	}

	private function parseEdit($parameter) {
		// TODO: implement
	}
}