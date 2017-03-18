<?php
namespace WEEEOpen\Tarallo\Query;


class QueryFieldParent extends QueryFieldSinglefield implements QueryField {
	public function validate() {
		if($this->isDefault()) {
			return true;
		}

		$content = $this->getContent();
		// TODO: implement
		return true;
	}

	public function parse($parameter) {
		$this->stopIfAlreadyParsed();
		if(!is_numeric($parameter)) {
			throw new \InvalidArgumentException($parameter . ' must be a number');
		}

		$this->content = (int) $parameter;
	}

	protected function getDefault() {
		return 0;
	}

	protected function nonDefaultToString() {
		return '/Parent/' . $this->getContent();
	}
}