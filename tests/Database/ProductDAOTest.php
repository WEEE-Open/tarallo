<?php


namespace WEEEOpen\TaralloTest\Database;


use WEEEOpen\Tarallo\Product;

class ProductDAOTest extends DatabaseTest {


	public function testProduct() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k', 'dunno');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);
	}

	public function testProductNoVariant() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);
	}

	//Is working, but need to find a way to discover if successfully is deleted
	//The test, by the way, is not working
	public function  testDeleteProduct(){
		$db = $this->getDb();

		$product = new Product('Samsong', 'JWOSQPA', 'black');
		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);

		$db->productDAO()->deleteProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);
	}
}