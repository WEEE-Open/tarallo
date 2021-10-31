<?php

namespace WEEEOpen\Tarallo;

trait ExceptionWithOtherItem
{
	/**
	 * @var string|null
	 */
	protected $otherItem = null;

	/**
	 * @param string|null $item
	 *
	 * @return $this
	 */
	public function setOtherItem(?string $item)
	{
		$this->otherItem = $item;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getOtherItem()
	{
		return $this->otherItem;
	}
}
