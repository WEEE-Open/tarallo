<?php

namespace WEEEOpen\Tarallo;

interface ItemWithProduct
{
	public function setProduct(?Product $product);
	public function getProduct(): ?Product;
}
