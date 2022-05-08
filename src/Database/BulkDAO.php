<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\DuplicateBulkIdentifierException;

final class BulkDAO extends DAO
{
	/**
	 * Add an entry to the bulk table
	 *
	 * @param string $identifier Bulk identifier
	 * @param string $type P or I (product or item)
	 * @param string $json The raw JSON
	 */
	public function addBulk(string $identifier, string $type, string $json)
	{

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
		} catch (\PDOException $e) {
			if ($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateBulkIdentifierException((string) $identifier, 'Bulk already exists: ' . (string) $identifier);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get all imports from BulkTable
	 */
	public function getBulkImports(): array
	{
		$statement = $this->getPDO()->query('SELECT Identifier, BulkIdentifier, `Time`, `User`, `Type`, `JSON` FROM BulkTable');
		$imports = $statement->fetchAll();
		return $imports;
	}

	/**
	 * Delete an entire bulk import via identifier
	 *
	 * @param string $identifier The bulk identifier
	 *
	 * @return bool True if anything was deleted, false otherwise
	 */
	public function deleteBulkImport(string $identifier): bool
	{
		$statement = $this->getPDO()->prepare('DELETE FROM BulkTable WHERE BulkIdentifier = :id');
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->execute();
			return $statement->rowCount() > 0;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Delete a bulk import via identifier
	 *
	 * @param int $identifier The identifier
	 *
	 * @return bool True if anything was deleted, false otherwise
	 */
	public function deleteImport(int $identifier): bool
	{
		$statement = $this->getPDO()->prepare('DELETE FROM BulkTable WHERE Identifier = :id');
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_INT);
			$statement->execute();
			return $statement->rowCount() > 0;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get an imported JSON from BulkTable and decodes it
	 *
	 * @param int $Identifier The identifier
	 *
	 * @return array|null The JSON as an array
	 */
	public function getDecodedJSON(int $Identifier): ?array
	{
		$statement = $this->getPDO()->prepare('SELECT JSON FROM BulkTable WHERE Identifier = :id');
		$importElement = null;
		try {
			$statement->bindValue(':id', $Identifier, \PDO::PARAM_INT);
			$statement->execute();
			$result = $statement->fetch();
			if ($result !== false) {
				$importElement = json_decode($result["JSON"], JSON_OBJECT_AS_ARRAY);
			}
		} finally {
			$statement->closeCursor();
		}

		return $importElement;
	}

	/**
	 * Check if there are entries with the same identifier and lock the rows for update
	 *
	 * @param string $identifier The bulk identifier
	 *
	 * @return bool
	 */
	public function bulkIdentifierExistsAndLocked(string $identifier): bool
	{
		$statement = $this->getPDO()->prepare('SELECT Identifier FROM BulkTable WHERE BulkIdentifier = :id FOR UPDATE');
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->execute();
			if ($statement->rowCount() === 0) {
				return false;
			}
			return true;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Check if an identifier (still) exists and lock the rows for update
	 *
	 * @param int $identifier The identifier
	 *
	 * @return bool
	 */
	public function identifierExistsAndLocked(int $identifier): bool
	{
		$statement = $this->getPDO()->prepare('SELECT Identifier FROM BulkTable WHERE Identifier = :id FOR UPDATE');
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_INT);
			$statement->execute();
			if ($statement->rowCount() === 0) {
				return false;
			}
			return true;
		} finally {
			$statement->closeCursor();
		}
	}
}
