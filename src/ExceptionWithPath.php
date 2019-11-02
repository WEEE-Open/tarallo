<?php


namespace WEEEOpen\Tarallo;


trait ExceptionWithPath {
	/**
	 * @var int[]|null
	 */
	protected $itemPath = null;

	/**
	 * @param int[]|null $itemPath
	 *
	 * @return $this
	 */
	public function setItemPath(?array $itemPath) {
		$this->itemPath = $itemPath;
		return $this;
	}

	/**
	 * @return int[]|null
	 */
	public function getItemPath(): ?array {
		return $this->itemPath;
	}
}
