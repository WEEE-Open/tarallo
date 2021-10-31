<?php

namespace WEEEOpen\Tarallo;

trait ItemTraitProduct
{
	/** @var Product|null */
	private $product;

	public function setProduct(?Product $product)
	{
		$this->product = $product;
		return $this;
	}

	public function getProduct(): ?Product
	{
		return $this->product;
	}
}
