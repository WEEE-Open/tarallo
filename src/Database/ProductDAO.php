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
			assert($result !== true, 'Add product');
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateItemCodeException((string) $product);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}

	}
}