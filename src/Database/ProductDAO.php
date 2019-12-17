<?php


namespace WEEEOpen\Tarallo\Database;


use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\String_;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\ItemWithProduct;
use WEEEOpen\Tarallo\Product;

final class ProductDAO extends DAO{
	public function addProduct(Product $product) {

		$statement = $this->getPDO()->prepare('INSERT INTO Product (`Brand`, `Model`, `Variant`) VALUES (:prod, :mod, :var)');
		try {
			$statement->bindValue(':prod', $product->getBrand(), \PDO::PARAM_STR);
			$statement->bindValue(':mod', $product->getModel(), \PDO::PARAM_STR);
			$variant = $product->getVariant() ? $product->getVariant() : '';
			$statement->bindValue(':var', $variant, \PDO::PARAM_STR);
			$result = $statement->execute();
			assert($result === true, 'Add product');
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateItemCodeException((string) $product);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}

		$this->database->featureDAO()->setFeatures($product);
	}

	/**
	 * It gets product in exact match, requires model, brand and variant
	 *
	 * @param Product $product
	 *
	 * @return Product
	 */
	public function getProductComplete(ProductCode $product): Product {
		//TODO: To implement
	}

	/**
	 *  It returns an array of product through brand and model. So it will get all variants of that product.
	 *
	 * @param String $brand
	 * @param String $model
	 *
	 * @return Product[]
	 */
	public function getProducts(String $brand, String $model): array {
		//TODO: To implement
	}

	public function getProduct(string $brand, string $model, ?string  $variant = ''): Product {
		$statement = $this->getPDO()->prepare('SELECT * FROM Product WHERE Brand = :prod AND Model = :mod AND Variant = :var');
		try{
			$statement->bindValue(':prod', $brand, \PDO::PARAM_STR);
			$statement->bindValue(':mod', $model, \PDO::PARAM_STR);
			$statement->bindValue(':var', $variant, \PDO::PARAM_STR);
			$result =  $statement->execute();
			assert($result === true, 'Get product');
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			$product = new Product($row['Brand'], $row['Model'], $row['Variant']);
		} finally{
			$statement->closeCursor();
		}

		return $product;
	}

	public function deleteProduct(Product $product) {
		$statement = $this->getPDO()->prepare('DELETE FROM Product WHERE Brand = :prod AND Model = :mod AND Variant = :var ');
		try{
			$statement->bindValue(':prod', $product->getBrand(), \PDO::PARAM_STR);
			$statement->bindValue(':mod', $product->getModel(), \PDO::PARAM_STR);
			$statement->bindValue(':var', $product->getVariant(), \PDO::PARAM_STR);
			$result =  $statement->execute();
			assert($result === true, 'Delete product');
		} finally{
			$statement->closeCursor();
		}
	}

	/**
	 * @param ItemWithProduct[] $items
	 *
	 * @return ItemWithProduct[]
	 */
	public function getProductsAll(array $items): array {
		foreach($items as $item) {
			$product = $this->getProductFromItem($item);
			$item->setProduct($product);
		}

		return $items;
	}

	private function getProductFromItem(ItemWithCode $item): ?Product {
		$statement = $this->getPDO()->prepare('SELECT Brand, Model, Variant FROM Item WHERE Code = :cod');
		try {
			$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);
			$result =  $statement->execute();
			assert($result === true, 'get product from item');
			if($statement->rowCount() === 0) {
				return null;
			}
			if($statement->rowCount() > 1) {
				throw new \LogicException("Item $item has multiple products");
			}
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			if($row['Brand'] === null || $row['Model'] === null || $row['Variant'] === null) {
				return null;
			} else {
				$product = new Product($row['Brand'], $row['Model'], $row['Variant']);
				$this->database->featureDAO()->getProductFeatures($product);
				return $product;
			}
		} finally{
			$statement->closeCursor();
		}
	}
}
