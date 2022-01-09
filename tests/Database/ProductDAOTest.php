<?php


namespace WEEEOpen\TaralloTest\Database;


use WEEEOpen\Tarallo\Database\DuplicateItemCodeException;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\Product;
use WEEEOpen\Tarallo\ProductCode;

/**
 * @covers \WEEEOpen\Tarallo\Database\ProductDAO
 */
class ProductDAOTest extends DatabaseTest {
	public function testProduct() {
		$db = $this->getDb();

		$product = new Product('Intel', 'K3k', 'dunno');

		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);
	}

	public function testProductExists1() {
		$db = $this->getDb();

		$product = new Product('Intel', 'Centrino DellaNonna', 'SLABC');

		$this->expectException(NotFoundException::class);
		$db->productDAO()->productMustExist($product);
	}

	public function testProductExists2() {
		$db = $this->getDb();

		$product = new Product('Intel', 'Centrino DellaNonna', 'SLABC');

		$db->productDAO()->addProduct($product);
		$db->productDAO()->productMustExist($product);

		$theProductAgain = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $theProductAgain);
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

	public function testDeleteProduct() {
		$db = $this->getDb();

		$product = new Product('Samsong', 'JWOSQPA', 'black');
		$db->productDAO()->addProduct($product);
		$gettedProduct = $db->productDAO()->getProduct($product);
		$this->assertEquals($product, $gettedProduct);

		$db->productDAO()->deleteProduct($product);
		$this->expectException(NotFoundException::class);
		$db->productDAO()->getProduct($product);
	}

	public function testDeleteNotExistingProduct() {
		$db = $this->getDb();

		$product = new Product('Samsong', 'JWOSQPA', 'black');

		$db->productDAO()->deleteProduct($product);
		$this->expectException(NotFoundException::class);
		$db->productDAO()->getProduct($product);
	}

	/**
	 * @covers \WEEEOpen\Tarallo\Database\ProductDAO
	 * @covers \WEEEOpen\Tarallo\Database\FeatureDAO
	 */
	public function testGetAllProducts() {
		$db = $this->getDb();

		$db->productDAO()->addProduct(
			(new Product("eMac", "EZ1600", "boh"))
				->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
				->addFeature(new Feature('color', 'white'))
				->addFeature(new Feature('type', 'case'))
		);
		$db->productDAO()->addProduct(
			(new Product("Intel", "MB346789", "v2.0"))
				->addFeature(new Feature('color', 'green'))
				->addFeature(new Feature('cpu-socket', 'lga771'))
				->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
				->addFeature(new Feature('parallel-ports-n', 1))
				->addFeature(new Feature('serial-ports-n', 1))
				->addFeature(new Feature('ps2-ports-n', 3))
				->addFeature(new Feature('usb-ports-n', 6))
				->addFeature(new Feature('ram-form-factor', 'dimm'))
				->addFeature(new Feature('ram-type', 'ddr2'))
				->addFeature(new Feature('type', 'motherboard'))
		);

		$SCHIFOMACCHINA = (new Item('SCHIFOMACCHINA'))
			->addFeature(new Feature('brand', 'eMac'))
			->addFeature(new Feature('model', 'EZ1600'))
			->addFeature(new Feature('variant', 'boh'))
			->addContent((new Item('B1337'))
					->addFeature(new Feature('brand', 'Intel'))
					->addFeature(new Feature('model', 'MB346789'))
					->addFeature(new Feature('variant', 'v2.0'))
					->addFeature(new Feature('working', 'yes'))
					->addFeature(new Feature('sn', 'TESTTEST'))
					->addContent((new Item('R42'))
							->addFeature(new Feature('brand', 'Samsung'))
							->addFeature(new Feature('model', 'S667ABC512'))
							->addFeature(new Feature('variant', 'v1'))
							->addFeature(new Feature('owner', 'DISAT'))
							->addFeature(new Feature('sn', 'ASD654321'))
							->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no')))
					->addContent((new Item('R634'))
							->addFeature(new Feature('brand', 'Samsung'))
							->addFeature(new Feature('model', 'S667ABC512'))
							->addFeature(new Feature('variant', 'v1'))
							->addFeature(new Feature('owner', 'DISAT'))
							->addFeature(new Feature('sn', 'ASD123456'))
							->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'))));

		$db->itemDAO()->addItem($SCHIFOMACCHINA);

		$gotIt = $db->itemDAO()->getItem(new ItemCode($SCHIFOMACCHINA->getCode()));
		$this->assertInstanceOf(Product::class, $gotIt->getProduct());
		$this->assertInstanceOf(Product::class, $gotIt->getContent()[0]->getProduct());
		$this->assertEquals(null, $gotIt->getContent()[0]->getContent()[0]->getProduct());
		$this->assertEquals(null, $gotIt->getContent()[0]->getContent()[1]->getProduct());

		// Motherboard serial number in the right place (item, not product)
		$this->assertEquals('TESTTEST', $gotIt->getContent()[0]->getFeature('sn'));
		$this->assertEquals(null, $gotIt->getContent()[0]->getProduct()->getFeature('sn'));

		// The opposite, but getFeatures also checks Product
		$this->assertEquals('lga771', $gotIt->getContent()[0]->getFeature('cpu-socket'));
		$this->assertEquals('lga771', $gotIt->getContent()[0]->getProduct()->getFeature('cpu-socket'));
	}

	public function testAddDuplicateProduct() {
		$db = $this->getDb();

		$db->productDAO()->addProduct(
			(new Product("eMac", "EZ1600", "boh"))
				->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
				->addFeature(new Feature('color', 'white'))
				->addFeature(new Feature('type', 'case'))
		);

		$this->expectException(DuplicateItemCodeException::class);

		$db->productDAO()->addProduct(
			(new Product("eMac", "EZ1600", "boh"))
				->addFeature(new Feature('motherboard-form-factor', 'atx'))
				->addFeature(new Feature('color', 'red'))
				->addFeature(new Feature('type', 'case'))
		);
	}

	public function testProductFeatureOverride() {
		$db = $this->getDb();

		$db->productDAO()->addProduct(
			(new Product("Intel", "Centryno", "SL666"))
				->addFeature(new Feature('frequency-hertz', 1500000000))
				->addFeature(new Feature('isa', 'x86-64'))
				->addFeature(new Feature('cpu-socket', 'lga771'))
				->addFeature(new Feature('color', 'grey'))
				->addFeature(new Feature('type', 'cpu'))
		);

		$item = (new Item('C123'))
			->addFeature(new Feature('brand', 'Intel'))
			->addFeature(new Feature('model', 'Centryno'))
			->addFeature(new Feature('variant', 'SL666'))
			->addFeature(new Feature('color', 'red'))
			->addFeature(new Feature('sn', 'AAAAAAAA'));
		$db->itemDAO()->addItem($item);

		$gotIt = $db->itemDAO()->getItem(new ItemCode('C123'));
		$this->assertEquals('red', $gotIt->getFeature('color'));
		$this->assertEquals('grey', $gotIt->getProduct()->getFeature('color'));
	}

	public function testGetAllVariants() {
		$db = $this->getDb();

		$db->productDAO()->addProduct(
			(new Product("Bbit", "BX535A3U", "rev 1.0"))
				->addFeature(new Feature('color', 'green'))
				->addFeature(new Feature('type', 'motherboard'))
		);

		$db->productDAO()->addProduct(
			(new Product("Bbit", "BX535A3U", "rev 2.0"))
				->addFeature(new Feature('color', 'red'))
				->addFeature(new Feature('type', 'motherboard'))
		);

		$db->productDAO()->addProduct(
			(new Product("Bbit", "BX535A3U", "rev 2.1"))
				->addFeature(new Feature('color', 'blue'))
				->addFeature(new Feature('type', 'motherboard'))
		);

		$products = $db->productDAO()->getProducts("Bbit", "BX535A3U");
		$this->assertIsArray($products);
		$this->assertCount(3, $products);
		$colors = [];
		foreach($products as $product) {
			$this->assertInstanceOf(Product::class, $product);
			$this->assertEquals('Bbit', $product->getBrand());
			$this->assertEquals('BX535A3U', $product->getModel());
			$this->assertEquals('motherboard', $product->getFeature('type'));
			$colors[$product->getVariant()] = $product->getFeature('color');
		}
		$this->assertEquals(['rev 1.0' => 'green', 'rev 2.0' => 'red', 'rev 2.1' => 'blue'], $colors);
	}

	public function testRenameProductBrand() {
		$db = $this->getDb();

		$initial = (new Product("Bbit", "BX535A3F", "rev 1.0"))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('type', 'motherboard'));
		$db->productDAO()->addProduct($initial);

		$db->productDAO()->renameProduct($initial, 'Cbit', null, null);

		$new = $db->productDAO()->getProduct(new ProductCode("Cbit", "BX535A3F", "rev 1.0"));
		$this->assertInstanceOf(Product::class, $new);
		$this->assertEquals('Cbit', $new->getBrand());
		$this->assertEquals('BX535A3F', $new->getModel());
		$this->assertEquals('rev 1.0', $new->getVariant());

		$this->expectException(NotFoundException::class);
		$old = $db->productDAO()->getProduct(new ProductCode("Bbit", "BX535A3U", "rev 1.0"));
	}

	public function testRenameProductModel() {
		$db = $this->getDb();

		$initial = (new Product("Bbit", "BX535A3F", "rev 1.0"))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('type', 'motherboard'));
		$db->productDAO()->addProduct($initial);

		$db->productDAO()->renameProduct($initial, null, 'BX1337Z42', null);

		$new = $db->productDAO()->getProduct(new ProductCode("Bbit", "BX1337Z42", "rev 1.0"));
		$this->assertInstanceOf(Product::class, $new);
		$this->assertEquals('Bbit', $new->getBrand());
		$this->assertEquals('BX1337Z42', $new->getModel());
		$this->assertEquals('rev 1.0', $new->getVariant());

		$this->expectException(NotFoundException::class);
		$old = $db->productDAO()->getProduct(new ProductCode("Bbit", "BX535A3U", "rev 1.0"));
	}

	public function testRenameProductVariant() {
		$db = $this->getDb();

		$initial = (new Product("Bbit", "BX535A3F", "rev 1.0"))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('type', 'motherboard'));
		$db->productDAO()->addProduct($initial);

		$db->productDAO()->renameProduct($initial, null, null, 'rev 2.2');

		$new = $db->productDAO()->getProduct(new ProductCode("Bbit", "BX535A3F", "rev 2.2"));
		$this->assertInstanceOf(Product::class, $new);
		$this->assertEquals('Bbit', $new->getBrand());
		$this->assertEquals('BX535A3F', $new->getModel());
		$this->assertEquals('rev 2.2', $new->getVariant());

		$this->expectException(NotFoundException::class);
		$old = $db->productDAO()->getProduct(new ProductCode("Bbit", "BX535A3U", "rev 1.0"));
	}

	public function testRenameProductAll() {
		$db = $this->getDb();

		$initial = (new Product("Bbit", "BX535A3F", "rev 1.0"))
			->addFeature(new Feature('color', 'green'))
			->addFeature(new Feature('type', 'motherboard'));
		$db->productDAO()->addProduct($initial);

		$db->productDAO()->renameProduct($initial, "Cbit", "BX1337Z42", "rev 2.2");

		$new = $db->productDAO()->getProduct(new ProductCode("Cbit", "BX1337Z42", "rev 2.2"));
		$this->assertInstanceOf(Product::class, $new);
		$this->assertEquals('Cbit', $new->getBrand());
		$this->assertEquals('BX1337Z42', $new->getModel());
		$this->assertEquals('rev 2.2', $new->getVariant());

		$this->expectException(NotFoundException::class);
		$old = $db->productDAO()->getProduct(new ProductCode("Bbit", "BX535A3U", "rev 1.0"));
	}
}
