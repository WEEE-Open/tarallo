<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldLanguage extends AbstractQueryField implements QueryField {
	public function __construct($parameter) {
		// TODO: check that it is a valid language
		$this->content = $parameter;
	}

	protected function nonDefaultToString() {
		return '/Language/' . $this->getContent();
	}
}