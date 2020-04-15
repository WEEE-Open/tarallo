<?php


namespace WEEEOpen\Tarallo\Database;


final class BulkDAO extends DAO {

	public function addBulk(String $identifier, String $type, String $json) {

		$statement = $this->getPDO()->prepare(
			'
INSERT INTO BulkTable (`BulkIdentifier`, `User`, `Type`, `JSON`) 
VALUES (:id, @taralloauditusername, :typ, :json)'
		);
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->bindValue(':typ', $type, \PDO::PARAM_STR);
			$statement->bindValue(':json', $json, \PDO::PARAM_STR);
			$result = $statement->execute();
			assert($result === true, 'Add bulk');
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateItemCodeException((string) $identifier, 'Bulk already exists: ' . (string) $identifier);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	public function checkDuplicatedIdentifier(String $identifier): bool {
		$statement = $this->getPDO()->prepare(
			'
		SELECT Identifier FROM BulkTable WHERE BulkIdentifier = :id
		'
		);
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->execute();
			if($statement->rowCount() === 0) {
				return false;
			}
			return true;
		} finally {
			$statement->closeCursor();
		}
	}

	public function deleteBulkIdentifier(String $bulkIdentifier) {
		$statement = $this->getPDO()->prepare(
			'
		DELETE FROM BulkTable WHERE BulkIdentifier = :id
		'
		);
		try {
			$statement->bindValue(':id', $bulkIdentifier, \PDO::PARAM_STR);
			$statement->execute();
		} finally {
			$statement->closeCursor();
		}
	}
}