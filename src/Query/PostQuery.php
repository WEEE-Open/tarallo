<?php
namespace WEEEOpen\Tarallo\Query;


class PostQuery extends AbstractQuery {
	protected $query = null;

	protected function getParseFields() {
		return [
			'Login'    => new QueryFieldLogin(),
			'Edit'     => new QueryFieldEdit(),
		];
	}

	public function fromString($string, $requestBody) {
		if(!is_string($string) || $string === '') {
			throw new \InvalidArgumentException('Query string must be a non-empty string');
		}

		$this->setBuilt();

		$pieces = explode('/', $this->normalizeString($string));
		if(count($pieces) > 1) {
			throw new \InvalidArgumentException('POST queries only allow one field, ' . count($pieces) . ' given');
		}
		$field = $pieces[0];

		if(isset($this->parseFields[ $field ])) {
			$this->parseFields[ $field ]->parse($requestBody);
			$this->query = $this->parseFields[ $field ];
		} else {
			throw new \InvalidArgumentException('Unknown field ' . $field);
		}

		return $this;
	}

	public function __toString() {
		if($this->query instanceof QueryFieldPostJSON) {
			return (string) $this->query;
		} else {
			return '{}';
		}
	}
}