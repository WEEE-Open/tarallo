<?php
namespace WEEEOpen\Tarallo\Query;


class GetQuery extends AbstractQuery {
	const FIELD_LOCATION = 'Location';
	const FIELD_SEARCH = 'Search';
	const FIELD_SORT = 'Sort';
	const FIELD_DEPTH = 'Depth';
	const FIELD_PARENT = 'Parent';
	const FIELD_LANGUAGE = 'Language';
	const FIELD_TOKEN = 'Token';

	protected function getParseFields() {
		return [
				self::FIELD_LOCATION => new QueryFieldLocation(),
				self::FIELD_SEARCH   => new QueryFieldSearch(),
				self::FIELD_SORT     => new QueryFieldSort(),
				self::FIELD_DEPTH    => new QueryFieldDepth(),
				self::FIELD_PARENT   => new QueryFieldParent(),
				self::FIELD_LANGUAGE => new QueryFieldLanguage(),
				self::FIELD_TOKEN    => new QueryFieldToken(),
			];
	}

	public function fromString($string) {
		if(!is_string($string) || $string === '') {
			throw new \InvalidArgumentException('Query string must be a non-empty string');
		}

		$this->setBuilt();

		$pieces = explode('/', $this->normalizeString($string));
		$this->parsePieces($pieces);

		return $this;
	}

	private function parsePieces($pieces) {
		$i = 0;
		$c = count($pieces);
		while($i < $c) {
			if(isset($this->parseFields[ $pieces[ $i ] ])) {
				if($i + 1 < $c) {
					$this->parseFields[ $pieces[ $i ] ]->parse($pieces[ $i + 1 ]);
					$i += 2;
				} else {
					throw new \InvalidArgumentException('Missing parameter for field ' . $pieces[ $i ]);
				}
			} else {
				throw new \InvalidArgumentException('Unknown field ' . $pieces[ $i ]);
			}
		}
	}

	public function __toString() {
		$result = '';
		foreach($this->parseFields as $field) {
			$result .= (string) $field;
		}
		return $result;
	}
}