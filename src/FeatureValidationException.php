<?php

namespace WEEEOpen\Tarallo;

use Throwable;

/**
 * When items are invalid other than for their location.
 */
class FeatureValidationException extends \RuntimeException {
	public $status = 400;
	public $item = null;
	public $feature = null;
	public $featurePath = null;

	public function __construct(?$feature = null, ?string $featurePath = null, ?string $item = null, $message = null, $code = 0, Throwable $previous = null) {
		$this->featurePath = $featurePath;
		if($item === null && $feature === null) {
			parent::__construct($message ?? 'Validation failed', $code, $previous);
		} elseif($feature === null) {
			parent::__construct($message ?? "Item $item is invalid", $code, $previous);
			$this->item = $item;
		} elseif($item === null) {
			parent::__construct($message ?? "Feature $feature is invalid", $code, $previous);
			$this->feature = $feature;
		} else {
			parent::__construct($message ?? "Feature $feature of item $item is invalid", $code, $previous);
			$this->item = $item;
			$this->feature = $feature;
		}
	}
}
