<?php


namespace WEEEOpen\Tarallo\Database;


use WEEEOpen\Tarallo\Product;

final class ProductDAO extends DAO{
	public function addProduct(Product $product) {

		$statement = $this->getPDO()->prepare('INSERT INTO Product (`Brand`, `Model`, `Variant`) VALUES (:prod, :mod, :var)');
		try {
			$statement->bindValue(':prod', $product->getBrand(), \PDO::PARAM_STR);
			$statement->bindValue(':mod', $product->getModel(), \PDO::PARAM_STR);
			$statement->bindValue(':var', $product->getVariant(), \PDO::PARAM_STR);
			$result = $statement->execute();
			//assert($result !== true, 'Add product');
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateItemCodeException((string) $product);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}

	}

	public function getProduct(string $brand, string $model, string  $variant): Product{
		$statement = $this->getPDO()->prepare('SELECT * FROM Product WHERE Brand = :prod AND Model = :mod AND Variant = :var');
		try{
			$statement->bindValue(':prod', $brand, \PDO::PARAM_STR);
			$statement->bindValue(':mod', $model, \PDO::PARAM_STR);
			$statement->bindValue(':var', $variant, \PDO::PARAM_STR);
			$result =  $statement->execute();
			//assert($result !== true, 'Get product');
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			$product = new Product($row['Brand'], $row['Model'], $row['Variant']);
		} finally{
			$statement->closeCursor();
		}

		return $product;
	}

	//Is this useful?
	public function deleteProduct(Product $product){
		$statement = $this->getPDO()->prepare('DELETE FROM Product WHERE Brand = :prod AND Model = :mod AND Variant = :var ');
		try{
			$statement->bindValue(':prod', $product->getBrand(), \PDO::PARAM_STR);
			$statement->bindValue(':mod', $product->getModel(), \PDO::PARAM_STR);
			$statement->bindValue(':var', $product->getVariant(), \PDO::PARAM_STR);
			$result =  $statement->execute();
			assert($result !== true, 'Delete product');
		} finally{
			$statement->closeCursor();
		}
	}
}