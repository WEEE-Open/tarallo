<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldToken extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		$this->content = $parameter;
	}

	protected function nonDefaultToString() {
		return '/Token/' . $this->getContent();
	}
}