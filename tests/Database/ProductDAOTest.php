<?php


namespace WEEEOpen\TaralloTest\Database;


use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\Product;

class ProductDAOTest extends DatabaseTest {
	public function testProduct() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k', 'dunno');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);
	}

	public function testProductDifferentObject() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k', 'dunno');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct(new Product('Intel', 'K3k', 'dunno'));
		$this->assertEquals($product, $gettedProduct);
	}

	public function testProductNotFound() {
		$db = $this->getDb();

		$product = new Product('Intel', 'Invalid', 'DoesNotExist');

		$this->expectException(NotFoundException::class);
		$db->productDAO()->getProduct($product);
	}

	public function testProductNotFoundDifferentVariant() {
		$db = $this->getDb();

		$product = new Product('Intel', 'E6400', 'SLAAA');

		$db->productDAO()->addProduct($product);
		$this->expectException(NotFoundException::class);
		$db->productDAO()->getProduct(new Product('Intel', 'E6400', 'SLBBB'));
	}

	public function testProductNoVariant() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);
	}

	public function testDeleteProduct(){
		$db = $this->getDb();

		$product = new Product('Samsong', 'JWOSQPA', 'black');
		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);

		$db->productDAO()->deleteProduct($product);
		$this->expectException(NotFoundException::class);
		$db->productDAO()->getProduct($product);
	}

	public function testDeleteNotExistingProduct(){
		$db = $this->getDb();

		$product = new Product('Samsong', 'JWOSQPA', 'black');

		$db->productDAO()->deleteProduct($product);
		$this->expectException(NotFoundException::class);
		$db->productDAO()->getProduct($product);
	}
}