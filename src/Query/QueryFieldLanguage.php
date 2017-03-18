<?php
namespace WEEEOpen\Tarallo\Query;

class QueryFieldLanguage extends QueryFieldSinglefield implements QueryField {
	public function validate() {
		return true;
	}

	public function parse($parameter) {
		$this->stopIfAlreadyParsed();
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