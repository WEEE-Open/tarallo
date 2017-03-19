<?php
namespace WEEEOpen\Tarallo\Query;


class PostQuery extends AbstractQuery {
	protected function getParseFields() {
		return [
			'Login' => 'parseLogin',
			'Edit'  => 'parseEdit',
		];
	}

	private function parsePieces($pieces) {
		$i = 0;
		$c = count($pieces);
		while($i < $c) {
			if(isset($this->parseFields[ $pieces[ $i ] ])) {
				// TODO: implement something
				//$fn = $this->parseFields[ $pieces[ $i ] ];
				//call_user_func($fn);
				//$i ++;
			}
		}
	}
}