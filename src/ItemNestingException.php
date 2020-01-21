<?php

namespace WEEEOpen\Tarallo;

use Throwable;

class ItemNestingException extends \RuntimeException {
	use ExceptionWithItem, ExceptionWithOtherItem, ExceptionWithPath, ExceptionWithFeature;
	public $status = 400;

	/**
	 * When items are placed in impossible places.
	 *
	 * @param string $item Item that can't be placed there
	 * @param string $parent Parent item that can't accept it
	 * @param array|null $path Item path, for GUIs
	 * @param string $message Explanation of the impossible nesting
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(?string $item = null, ?string $parent = null, ?array $path = null, $message = null, $code = 0, Throwable $previous = null) {
		if($item === null && $parent === null) {
			parent::__construct($message ?? "Cannot place item there", $code, $previous);
		} elseif($item === null) {
			parent::__construct($message ?? "Cannot place that kind of item inside $parent", $code, $previous);
		} elseif($parent === null) {
			parent::__construct($message ?? "Cannot place $item inside that kind of item", $code, $previous);
		} else {
			parent::__construct($message ?? "Cannot place $item inside $parent", $code, $previous);
		}
		$this->item = $item;
		$this->otherItem = $parent;
		$this->itemPath = $path;
	}
}
