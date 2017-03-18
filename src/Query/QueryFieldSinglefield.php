<?php
namespace WEEEOpen\Tarallo\Query;


abstract class QueryFieldSinglefield extends AbstractQueryField implements QueryField {
	private $parsed = false;

	protected function stopIfAlreadyParsed() {
		if($this->parsed === true) {
			throw new \InvalidArgumentException('Invalid duplicate parameter in query string');
		} else {
			$this->parsed = true;
		}
	}

}