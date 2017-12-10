<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

class GetQuery extends AbstractQuery {
	const FIELD_CODE = 'Code';
	const FIELD_SEARCH = 'Search';
	const FIELD_SORT = 'Sort';
	const FIELD_DEPTH = 'Depth';
	const FIELD_LOCATION = 'Depth';
	const FIELD_PARENT = 'Parent';
	const FIELD_PAGE = 'Page';
	const FIELD_LANGUAGE = 'Language';
	const FIELD_TOKEN = 'Token';

	private $queryFields = [];

	/**
	 * @param $query string representing search fields (Search, Sort, Parent, etc...)
	 * @param $parameter string parameters passed to QueryField constructor
	 *
	 * @return null|Field\QueryField
	 * @throws InvalidParameterException for unknown parameters
	 */
	protected function queryFieldsFactory($query, $parameter) {
		switch($query) {
			case self::FIELD_CODE:
				return new Field\Code($parameter);
			case self::FIELD_SEARCH:
				return new Field\Search($parameter);
			case self::FIELD_SORT:
				return new Field\Sort($parameter);
			case self::FIELD_DEPTH:
				return new Field\Depth($parameter);
			case self::FIELD_LOCATION:
				throw new InvalidParameterException('Unsupported field Location');
			case self::FIELD_PARENT:
				return new Field\ParentField($parameter);
			case self::FIELD_PAGE:
				return new Field\Page($parameter);
			case self::FIELD_LANGUAGE:
				return new Field\Language($parameter);
			case self::FIELD_TOKEN:
				return new Field\Token($parameter);
			default:
				throw new InvalidParameterException('Unknown field ' . $query);
		}
	}

	protected function fromPieces($pieces) {
		$i = 0;
		$c = count($pieces);

		while($i < $c) {
			if($i + 1 < $c) {
				$previous = $this->getQueryField($pieces[$i]);
				if($previous === null) {
					$this->addQueryField($pieces[$i],
						$this->queryFieldsFactory($pieces[$i], $pieces[$i + 1]));
				} else {
					/**
					 * @var $previous Field\QueryField
					 */
					$previous->add($pieces[$i + 1]);
				}
				$i += 2;
			} else {
				throw new InvalidParameterException('Missing parameter for field ' . $pieces[$i]);
			}
		}
	}


	protected function addQueryField($name, Field\QueryField $qf) {
		$this->queryFields[$name] = $qf;
	}

	protected function getAllQueryFields() {
		return $this->queryFields;
	}

	protected function getQueryField($name) {
		if(isset($this->queryFields[$name])) {
			return $this->queryFields[$name];
		} else {
			return null;
		}
	}

	protected function normalizeString($string) {
		if(substr($string, 0, 1) === '/') {
			$string = substr($string, 1); // remove first slash
		}
		if(substr($string, - 1) === '/') {
			$string = substr($string, 0, strlen($string) - 1);
		}

		return $string;
	}

	public final function __construct($string) {
		if(!is_string($string) || $string === '') {
			throw new InvalidParameterException('Query string must be a non-empty string');
		}

		$pieces = explode('/', $this->normalizeString($string));
		$this->fromPieces($pieces);

		return $this;
	}

	public function __toString() {
		$result = '';
		$queries = $this->getAllQueryFields();
		foreach($queries as $field) {
			$result .= (string) $field;
		}

		return $result;
	}

	public function run($user, Database $db) {
		if($user === null && !isset($this->queryFields[self::FIELD_TOKEN])) {
			throw new InvalidParameterException('Not logged in and no token provided');
		}
		/** @var Field\QueryField[] $qf */
		$qf = $this->queryFields; // this is only needed because PHPStorm doesn't understand "$this->queryFields" in PHPDoc comments.
		$codes = isset($qf[self::FIELD_CODE]) ? $qf[self::FIELD_CODE]->getContent() : null;
		$search = isset($qf[self::FIELD_SEARCH]) ? $qf[self::FIELD_SEARCH]->getContent() : null;
		$depth = isset($qf[self::FIELD_DEPTH]) ? $qf[self::FIELD_DEPTH]->getContent() : null;
		$location = isset($qf[self::FIELD_LOCATION]) ? $qf[self::FIELD_LOCATION]->getContent() : null;
		$page = isset($qf[self::FIELD_PAGE]) ? $qf[self::FIELD_PAGE]->getContent() : 1;
		$parent = isset($qf[self::FIELD_PARENT]) ? $qf[self::FIELD_PARENT]->getContent() : null;
		$sort = isset($qf[self::FIELD_SORT]) ? $qf[self::FIELD_SORT]->getContent() : null;
		$token = isset($qf[self::FIELD_TOKEN]) ? $qf[self::FIELD_TOKEN]->getContent() : null;

		return ['items' => $db->itemDAO()->getItem($codes, $search, $depth, $parent, $sort, $token, $location, $page, 20)];
	}
}