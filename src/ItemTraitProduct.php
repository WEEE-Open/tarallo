<?php


namespace WEEEOpen\Tarallo;


trait ItemTraitProduct {
	private $product;

	public function setProduct(?Product $product) {
		$this->product = $product;
	}
}
