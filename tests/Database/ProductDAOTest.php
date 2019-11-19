<?php


namespace WEEEOpen\TaralloTest\Database;


use WEEEOpen\Tarallo\Product;

class ProductDAOTest extends DatabaseTest {

	//This, I hope, is (very) temporary
	private function createExampleProducts(){
		$db = $this->getDb();

		//$db->productDAO()->addProduct(new Product('Intel', 'K3k', 'dunno'));
		$db->productDAO()->addProduct(new Product('Intel', 'Lm40'));
		$db->productDAO()->addProduct(new Product('Samsong', 'JWOSQPA', 'black'));
		$db->productDAO()->addProduct(new Product('Dell', '19942', 'HD'));

	}

	public function testProduct() {
		$this->createExampleProducts();
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k', 'dunno');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product->getBrand(), $product->getModel(), $product->getVariant());
		$this->assertEquals($product, $gettedProduct);
	}

	public function testProductNoVariant() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product->getBrand(), $product->getModel());
		$this->assertEquals($product, $gettedProduct);
	}

	//Is working, but need to find a way to discover if successfully is deleted
	//The test, by the way, is not working
	public function  testDeleteProduct(){
		$db = $this->getDb();

		$this->createExampleProducts();
		$product = new Product('Samsong', 'JWOSQPA', 'black');
		$db->productDAO()->deleteProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product->getBrand(), $product->getModel(), $product->getVariant());
		$this->assertEquals($product, $gettedProduct);
	}
}