<?php


namespace WEEEOpen\Tarallo\Server;


trait ItemTraitOptionalCode {
	use ItemTraitCode {
		__construct as private otherConstruct;
		getCode as private getCodeAlways;
	}

	/**
	 * Create an Item
	 *
	 * @param string|null $code Item code, or null if not yet known
	 */
	public function __construct($code) {
		if($code === null) {
			$this->code = null;
		} else {
			$this->otherConstruct($code);
		}
	}

	/**
	 * Set code, unless it has already been set
	 *
	 * @param string $code Item code
	 */
	public function setCode($code) {
		if($this->code !== null) {
			throw new \LogicException("Cannot change code for item {$this->getCode()} since it's already set");
		}

		$this->otherConstruct($code);
	}

	/**
	 * Get code, if it's set
	 *
	 * @return string
	 * @see setCode
	 */
	public function getCode(): string {
		if($this->code === null) {
			throw new \LogicException('Trying to read code from an Item without code');
		}

		return $this->getCodeAlways();
	}

	/**
	 * Has code already been set? Do we need to generate it?
	 *
	 * @return bool
	 *
	 * @see setCode to set it
	 */
	public function hasCode() {
		return $this->code !== null;
	}
}