<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldDepth extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		if(!is_numeric($parameter)) {
			throw new \InvalidArgumentException($parameter . ' must be a number');
		}

		$this->content = (int) $parameter;

		if($this->content < 0) {
			throw new \InvalidArgumentException('Depth must be >= 0, ' . $parameter . ' given');
		}
	}

	protected function getDefault() {
		return 0;
	}

	protected function nonDefaultToString() {
		return '/Depth/' . $this->getContent();
	}
}