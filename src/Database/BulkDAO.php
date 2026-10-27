<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\ItemWithFeatures;
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

	/**
	 * Find products in BulkTable that match an item (by brand, model, type) using fuzzy search.
	 *
	 * @param ItemWithFeatures $from The item to find matching products for
	 *
	 * @return array List of candidate products sorted by score descending
	 */
	public function findMatchingProduct($from): array
	{
		$itemBrand = (string) ($from->getFeatureValue('brand') ?? '');
		$itemModel = (string) ($from->getFeatureValue('model') ?? '');
		$itemType  = (string) ($from->getFeatureValue('type') ?? '');

		$statement = $this->getPDO()->prepare('SELECT Identifier, BulkIdentifier, JSON FROM BulkTable WHERE Type = :type');
		$matches = [];
		try {
			$statement->bindValue(':type', 'P');
			$statement->execute();
			$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($rows as $row) {
				$data = json_decode($row['JSON'], true);
				if (!is_array($data)) {
					continue;
				}

				$productBrand = (string) ($data['brand'] ?? $data['features']['brand'] ?? '');
				$productModel = (string) ($data['model'] ?? $data['features']['model'] ?? '');
				$productType  = (string) ($data['features']['type'] ?? '');

				if ($itemType !== '' && $productType !== '' && strcasecmp($itemType, $productType) !== 0) {
					continue; // Not the same hardware component
				}

				// Check model similarity
				similar_text(strtolower($itemModel), strtolower($productModel), $modelPercent);
				// Check brand similarity
				similar_text(strtolower($itemBrand), strtolower($productBrand), $brandPercent);
				// Combined weighted score (Model is 70% important, Brand is 30%)
				$score = ($modelPercent * 0.7) + ($brandPercent * 0.3);
				// If similarity score >= 50%, it's a match!
				if ($score >= 50.0) {
					$matches[] = [
						'id' => (int) $row['Identifier'],
						'bulkIdentifier' => $row['BulkIdentifier'], // batch name, e.g. "OilData"
						'brand' => $productBrand,
						'model' => $productModel,
						'variant' => $data['variant'] ?? 'default',
						'score' => round($score, 1),
						'data' => $data,
					];
				}
			}
		} finally {
			$statement->closeCursor();
		}

		// Sort by score descending (best matches first)
		usort($matches, static function ($a, $b) {
			return $b['score'] <=> $a['score'];
		});

		return $matches;
	}
}
