<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldLanguage extends QueryFieldSinglefield implements QueryField {
	public function parse($parameter) {
		$this->stopIfAlreadyParsed();
		// TODO: check that it is a valid language
		$this->content = $parameter;
	}

	protected function getDefault() {
		// TODO: change default?
		return 'it-IT';
	}

	protected function nonDefaultToString() {
		return '/Language/' . $this->getContent();
	}
}