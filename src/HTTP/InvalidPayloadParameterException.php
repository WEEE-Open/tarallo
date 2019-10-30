<?php

namespace WEEEOpen\Tarallo\HTTP;

/**
 * @deprecated see https://github.com/WEEE-Open/tarallo/issues/66
 * @package WEEEOpen\Tarallo\HTTP
 */
class InvalidPayloadParameterException extends \RuntimeException {
	public $status = 400;
	private $parameter;
	private $reason;

	/**
	 * InvalidPayloadParameterException constructor (you don't say)
	 * If value and reason are null (which is their default), parameter
	 * is assumed to be mandatory but missing and that will be used as reason.
	 *
	 * @param string $parameter Parameter that caused the exception
	 * @param string|null $value Invalid value, used to generate a message only if reason is null.
	 * @param string|null $reason
	 */
	public function __construct($parameter, $value = null, $reason = null) {
		if($reason === null) {
			if($value === null) {
				$reason = "$parameter must be present";
			} else {
				$reason = "value $value is unacceptable";
			}
		}

		parent::__construct("Invalid parameter $parameter: " . $reason);

		$this->parameter = $parameter;
		$this->reason = $reason;
	}

	/**
	 * Parameter that caused the exception
	 *
	 * @return string
	 */
	public function getParameter() {
		return $this->parameter;
	}

	/**
	 * Reason why the parameter is invalid
	 *
	 * @return string
	 */
	public function getReason() {
		return $this->reason;
	}
}
