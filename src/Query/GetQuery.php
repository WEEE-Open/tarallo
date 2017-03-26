<?php
namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

class GetQuery extends AbstractQuery {
	const FIELD_LOCATION = 'Location';
	const FIELD_SEARCH = 'Search';
	const FIELD_SORT = 'Sort';
	const FIELD_DEPTH = 'Depth';
	const FIELD_PARENT = 'Parent';
	const FIELD_LANGUAGE = 'Language';
	const FIELD_TOKEN = 'Token';

	protected function queryFieldsFactory($query, $parameter) {
		switch($query) {
			case self::FIELD_LOCATION:
				return new Field\Location($parameter);
			case self::FIELD_SEARCH:
				return new Field\Search($parameter);
			case self::FIELD_SORT:
				return new Field\Sort($parameter);
			case self::FIELD_DEPTH:
				return new Field\Depth($parameter);
			case self::FIELD_PARENT:
				return new Field\ParentField($parameter);
			case self::FIELD_LANGUAGE:
				return new Field\Language($parameter);
			case self::FIELD_TOKEN:
				return new Field\Token($parameter);
			default:
				throw new InvalidParameterException('Unknown field ' . $query);
		}
	}

	protected function fromPieces($pieces, $requestBody) {
		$i = 0;
		$c = count($pieces);

		while($i < $c) {
			if($i + 1 < $c) {
				$previous = $this->getQueryField($pieces[ $i ]);
				if($previous === null) {
					$this->addQueryField($pieces[ $i ],
						$this->queryFieldsFactory($pieces[ $i ], $pieces[ $i + 1 ]));
				} else {
					/**
					 * @var $previous Field\QueryField
					 */
					$previous->add($pieces[ $i + 1 ]);
				}
				$i += 2;
			} else {
				throw new InvalidParameterException('Missing parameter for field ' . $pieces[ $i ]);
			}
		}
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
		if(!$this->isBuilt()) {
			throw new \LogicException('Cannot run a query without building it first');
		}
		// TODO: Implement run() method.
	}
}