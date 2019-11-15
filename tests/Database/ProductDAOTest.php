<?php


namespace WEEEOpen\TaralloTest\Database;


use WEEEOpen\Tarallo\Product;

class ProductDAOTest extends DatabaseTest {

	public function testProduct() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K9k', 'dunno');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product->getBrand(), $product->getModel(), $product->getVariant());
		$this->assertEquals($product, $gettedProduct);
	}
}