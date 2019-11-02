<?php


namespace WEEEOpen\Tarallo;


trait ExceptionWithItem {
	/**
	 * @var string|null
	 */
	protected $item = null;

	/**
	 * @param string|null $item
	 *
	 * @return $this
	 */
	public function setItem(?string $item) {
		$this->item = $item;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getItem() {
		return $this->item;
	}
}
