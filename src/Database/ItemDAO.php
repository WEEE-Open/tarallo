<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemPrefixer;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\FoundException;
use WEEEOpen\Tarallo\ProductCode;
use WEEEOpen\Tarallo\ValidationException;

final class ItemDAO extends DAO
{
	/**
	 * Insert a single item into the database
	 *
	 * @param ItemWithFeatures $item the item to be inserted
	 * @param ItemWithCode $parent parent item
	 *
	 * @throws DuplicateItemCodeException If any item with same code already exists
	 */
	public function addItem(ItemWithFeatures $item, ItemWithCode $parent = null)
	{
		assert($item instanceof ItemIncomplete || $item instanceof Item);
		if (!$item->hasCode()) {
			$prefix = ItemPrefixer::get($item);
			$code = $this->getNewCode($prefix);
			$item->setCode($code);
		}

		$statement = $this->getPDO()->prepare('INSERT INTO Item (`Code`, Token) VALUES (:cod, :tok)');

		try {
			$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);
			$statement->bindValue(':tok', $item->getToken(), \PDO::PARAM_STR);
			$result = $statement->execute();
			assert($result !== false, 'insert item');
		} catch (\PDOException $e) {
			if ($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateItemCodeException($item->getCode());
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}

		/** @var Item $item */
		$this->database->featureDAO()->setFeatures($item);
		$this->database->treeDAO()->addToTree($item, $parent);

		$childItems = $item->getContent();
		foreach ($childItems as $childItem) {
			// yay recursion!
			$this->addItem($childItem, $item);
		}
	}

	/**
	 * Soft-delete an item (mark as deleted to make invisible, detach from tree)
	 *
	 * @param ItemWithCode $item
	 * @param string $newCode
	 *
	 * @throws ValidationException if item contains other items (cannot be deleted)
	 */
	public function renameItem(ItemWithCode $item, string $newCode)
	{
		$this->itemMustExist($item);
		$statement = $this->getPDO()->prepare('UPDATE Item SET Code=:cod WHERE `Code` = :old');

		try {
			$statement->bindValue(':cod', $newCode, \PDO::PARAM_STR);
			$statement->bindValue(':old', $item->getCode(), \PDO::PARAM_STR);
			$result = $statement->execute();
			assert($result !== false, 'update item');
		} catch (\PDOException $e) {
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Soft-delete an item (mark as deleted to make invisible, detach from tree)
	 *
	 * @param ItemWithCode $item
	 *
	 * @throws ValidationException if item contains other items (cannot be deleted)
	 */
	public function deleteItem(ItemWithCode $item)
	{
		$this->itemMustExist($item);
		$statement = $this->getPDO()->prepare('UPDATE Item SET DeletedAt = NOW() WHERE `Code` = ?');

		try {
			$statement->execute([$item->getCode()]);
		} catch (\PDOException $e) {
			if ($e->getCode() === '45000' && $statement->errorInfo()[2] === 'Cannot delete an item while contains other items') {
				throw new ValidationException($item->getCode(), null, 'Cannot delete an item while contains other items', 0, $e);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Lose an item (mark as lost, detach from tree)
	 *
	 * @param ItemWithCode $item
	 */
	public function loseItem(ItemWithCode $item)
	{
		$this->itemMustExist($item);
		$statement = $this->getPDO()->prepare('UPDATE Item SET LostAt = NOW() WHERE `Code` = ?');

		try {
			$statement->execute([$item->getCode()]);
		} catch (\PDOException $e) {
			if (
				$e->getCode() === '45000'
				&& $statement->errorInfo()[2] === 'Cannot mark an item as lost while it contains other items'
			) {
				throw new ValidationException($item->getCode(), null, 'Cannot mark an item as lost while it contains other items');
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Yes, it's kinda like the true/false/FileNotFound thing.
	 *
	 * @param ItemWithCode $item
	 *
	 * @return bool|null tri-state: true if marked as deleted, false if not marked but exists, null if doesn't exist
	 * @deprecated This just doesn't cut it anymore with lost items
	 */
	private function itemIsDeleted(ItemWithCode $item)
	{
		$statement = $this->getPDO()
			->prepare('SELECT IF(DeletedAt IS NULL, FALSE, TRUE) FROM Item WHERE `Code` = :cod');
		try {
			$statement->execute([$item->getCode()]);
			if ($statement->rowCount() === 0) {
				return null;
			}
			$result = $statement->fetch(\PDO::FETCH_NUM);
			$exists = (bool) $result[0];

			return $exists;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * True if item exists in any form (even if marked as deleted), false otherwise.
	 *
	 * @param ItemWithCode $item
	 *
	 * @return bool
	 * @deprecated Use in tests only, replace with itemMustExist
	 * @see itemVisible to see if item is visible or marked as deleted
	 */
	public function itemExists(ItemWithCode $item): bool
	{
		$deleted = $this->itemIsDeleted($item);
		if ($deleted === null) {
			return false;
		}

		return true;
	}

	/**
	 * True if item is visibile (exists AND not marked as deleted), false otherwise (doesn't exist or is marked as
	 * deleted)
	 *
	 * @param ItemWithCode $item
	 *
	 * @return bool
	 * @deprecated Use in tests only, replace with itemMustExist
	 * @see itemExists to check wether item exists at all or not
	 */
	public function itemVisible(ItemWithCode $item): bool
	{
		$deleted = $this->itemIsDeleted($item);
		if ($deleted === false) {
			return true;
		}

		return false;
	}

	/**
	 * Ensure that an Item exists in the Item table and lock its row
	 *
	 * @param ItemWithCode $item
	 * @param bool $allowDeleted True if a deleted item is acceptable as "existing", false if deleted items should be
	 * ignored
	 */
	public function itemMustExist(ItemWithCode $item, bool $allowDeleted = false)
	{
		// Use Item instead of ProductItemFeatures because we need to check DeletedAt, and we will modify Item anyway
		if ($allowDeleted) {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod FOR UPDATE');
		} else {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod and `DeletedAt` IS NULL FOR UPDATE');
		}
		try {
			$statement->execute([$item->getCode()]);
			if ($statement->rowCount() === 0) {
				throw new NotFoundException();
			}
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Ensure that an Item exists in the Item table and lock its row
	 *
	 * @param ItemWithCode $item
	 * @param bool $allowDeleted True if a deleted item is acceptable as "existing", false if deleted items should be
	 * ignored
	 */
	public function itemMustNotExist(ItemWithCode $item, bool $allowDeleted = false)
	{
		// Use Item instead of ProductItemFeatures because we need to check DeletedAt, and we will modify Item anyway
		if ($allowDeleted) {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod FOR UPDATE');
		} else {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod and `DeletedAt` IS NULL FOR UPDATE');
		}
		try {
			$statement->execute([$item->getCode()]);
			if ($statement->rowCount() > 0) {
				throw new FoundException();
			}
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get an Item code, if the item exists. Useful to normalize case.
	 * Use itemMustExist if you're going to modify the item in any way.
	 *
	 * @param string $itemCode
	 * @param bool $allowDeleted True if a deleted item is acceptable as "existing", false if deleted items should be
	 * ignored
	 *
	 * @return ItemCode|null
	 * @see itemMustExist
	 */
	public function getActualItemCode(string $itemCode, bool $allowDeleted = false): ?ItemCode
	{
		if ($allowDeleted) {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod');
		} else {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod and `DeletedAt` IS NULL');
		}
		try {
			$statement->execute([$itemCode]);
			if ($statement->rowCount() === 0) {
				return null;
			} else {
				return new ItemCode($statement->fetchAll(\PDO::FETCH_NUM)[0][0]);
			}
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get deletion date for an item, null if not deleted or doesn't exist
	 *
	 * @param ItemWithCode $item
	 *
	 * @return null|string
	 * @deprecated use getExtraData instead
	 */
	public function itemDeletedAt(ItemWithCode $item)
	{
		$statement = $this->getPDO()->prepare('SELECT DeletedAt FROM Item WHERE `Code` = ?');
		try {
			$statement->execute([$item->getCode()]);
			if ($statement->rowCount() === 0) {
				return null;
			}
			$result = $statement->fetch(\PDO::FETCH_NUM);

			return $result[0];
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Undo soft deletion of an item. It will turn into a root item, wether you want that or not: place it somewhere
	 * right after that.
	 *
	 * @param ItemWithCode $item
	 */
	public function undelete(ItemWithCode $item)
	{
		$statement = $this->getPDO()->prepare('UPDATE Item SET DeletedAt = NULL WHERE `Code` = ?');
		try {
			$statement->execute([$item->getCode()]);
			if ($statement->rowCount() === 0) {
				throw new NotFoundException();
			}
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Undo losing an item, when it's found again. It will turn into a root item, wether you want that or not: place it
	 * somewhere right after that.
	 *
	 * @param ItemWithCode $item
	 */
	public function unlose(ItemWithCode $item)
	{
		$statement = $this->getPDO()->prepare('UPDATE Item SET LostAt = NULL WHERE `Code` = ?');
		try {
			$statement->execute([$item->getCode()]);
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Rename items without a product, when the product is created but it has a different name.
	 *
	 * @param ProductCode $old Old product that didn't exist
	 * @param ProductCode $new New product that exists
	 */
	public function renameItemsWithoutProduct(ProductCode $old, ProductCode $new)
	{
		// Cannot SELECT Code from Item:
		// Error in query (1442): Can't update table 'Item' in stored function/trigger because it is already used by statement which invoked this stored function/trigger
		$statement = $this->getPDO()->prepare('
UPDATE ItemFeature SET ValueText = :newValue
WHERE Feature = :feature
AND Code IN (
	SELECT f1.`Code`
    FROM ItemFeature AS f1, ItemFeature AS f2, ItemFeature AS f3
    WHERE f1.Code = f2.Code AND f2.Code = f3.Code
	AND f1.Feature = \'brand\' AND f2.Feature = \'model\' AND f3.Feature = \'variant\'
	AND f1.ValueText = :b
	AND f2.ValueText = :m
	AND f3.ValueText = :v
)');
		try {
			$statement->bindValue(':b', $old->getBrand());
			$statement->bindValue(':m', $old->getModel());
			$statement->bindValue(':v', $old->getVariant());

			if ($old->getBrand() !== $new->getBrand()) {
				$statement->bindValue(':feature', 'brand');
				$statement->bindValue(':newValue', $new->getBrand());

				$result = $statement->execute();
				assert($result !== false, 'rename items without a product 1');
				$statement->bindValue(':b', $new->getBrand());
			}
			if ($old->getModel() !== $new->getModel()) {
				$statement->bindValue(':feature', 'model');
				$statement->bindValue(':newValue', $new->getModel());

				$result = $statement->execute();
				assert($result !== false, 'rename items without a product 2');
				$statement->bindValue(':m', $new->getModel());
			}
			if ($old->getVariant() !== $new->getVariant()) {
				$statement->bindValue(':feature', 'variant');
				$statement->bindValue(':newValue', $new->getVariant());

				$result = $statement->execute();
				assert($result !== false, 'rename items without a product 3');
				//$statement->bindValue(':m', $new->getVariant());
			}
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get a new sequential code directly from database, for a given prefix
	 *
	 * @param string $prefix
	 *
	 * @return string
	 */
	private function getNewCode($prefix)
	{
		$statement = $this->getPDO()->prepare(
		/** @lang MySQL */
			'SELECT GenerateCode(?)'
		);
		try {
			$statement->execute([$prefix]);
			$code = $statement->fetch(\PDO::FETCH_NUM)[0];
			if ($code === null) {
				throw new \LogicException("Cannot generate code for prefix $prefix, NULL returned");
			}

			return (string) $code;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get a single item, its content, all related products
	 *
	 * @param ItemWithCode $itemToGet
	 * @param string|null $token
	 * @param int|null $depth max depth
	 *
	 * @return Item
	 */
	public function getItem(ItemWithCode $itemToGet, $token = null, int $depth = null)
	{
		if ($token !== null && !$this->checkToken($itemToGet, $token)) {
			throw new NotFoundException($itemToGet->peekCode());
		}

		if ($depth === null) {
			$depth = 20;
		}

		// TODO: we can also select Depth here, may be useful to select depth = maxdepth + 1 and see if there are items inside and discard them, but it's slow and unefficient...
		$statement = $this->getPDO()->prepare(
			<<<EOQ
			SELECT `Descendant` AS `Code`, GetParent(`Descendant`) AS Parent
			FROM Tree
			WHERE Ancestor = ?
			AND Depth <= ?
			ORDER BY Depth ASC, `Code` ASC
EOQ
			// Adding "CHAR_LENGTH(`Code`) ASC" to ORDER BY makes it sort items like "R54, C124, R252, R253"...
		);


		/**
		 * All items in this subtree, flattened
		 */
		$flat = [];

		try {
			$result = $statement->execute([$itemToGet->getCode(), $depth]);
			assert($result !== false, 'get root item (in a subtree)');

			// First Item is the head Item
			if (($row = $statement->fetch(\PDO::FETCH_ASSOC)) === false) {
				throw new NotFoundException($itemToGet->getCode());
			} else {
				// Now we have the real code, with correct case (database is case-insensitive)
				$head = new Item($row['Code']);
				$flat[$head->getCode()] = $head;
				$itemToGet = null;
			}

			// Other items
			while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$parent = $flat[$row['Parent']];
				if (!isset($parent)) {
					throw new \LogicException(
						'Broken tree: got ' . $row['Code'] . ' before its parent ' . $row['Parent']
					);
				}
				$current = $flat[$row['Code']] = new Item($row['Code']);
				$parent->addContent($current);
			}
			$parent = null;
			unset($parent);
		} finally {
			$statement->closeCursor();
		}
		$this->database->treeDAO()->getPathTo($head);
		$this->database->featureDAO()->getFeaturesAll($flat);
		$this->database->productDAO()->getProductsAll($flat);
		$this->database->donationsDAO()->addDonationsToItem($head);
		$this->getExtraData($head);

		return $head;
	}

	/**
	 * Check that item can be obtained with a token.
	 *
	 * @param ItemWithCode $item
	 * @param string $token
	 *
	 * @return bool true if possible, false if wrong token or item doesn't exist
	 */
	private function checkToken(ItemWithCode $item, string $token)
	{
		$tokenquery = $this->getPDO()->prepare(
			<<<EOQ
			SELECT IF(COUNT(*) > 0, TRUE, FALSE)
			FROM Item
			WHERE `Code` = ? AND Token = ?
EOQ
		);

		try {
			$tokenquery->execute([$item->getCode(), $token]);
			$result = $tokenquery->fetch(\PDO::FETCH_NUM);
			// MySQL doesn't understand booleans, they're just tinyints, and we get that lack of abstraction slapped right on our face because yes.
			$exists = (bool) $result[0];

			return $exists;
		} finally {
			$tokenquery->closeCursor();
		}
	}

	/**
	 * Get extra data: deletion date and lost date for an item.
	 *
	 * @param Item $head
	 */
	private function getExtraData(Item &$head)
	{
		// TODO: race conditions with other queries?
		$statement = $this->getPDO()
			->prepare(
				'SELECT UNIX_TIMESTAMP(DeletedAt) AS DeletedAt, UNIX_TIMESTAMP(LostAt) AS LostAt FROM Item WHERE `Code` = ?'
			);
		try {
			$statement->execute([$head->getCode()]);
			if ($statement->rowCount() === 0) {
				throw new \LogicException('Item disappeared during a query');
			}
			$result = $statement->fetch(\PDO::FETCH_ASSOC);
			try {
				if ($result['DeletedAt'] !== null) {
					$head->setDeletedAt(\DateTime::createFromFormat('U.u', $result['DeletedAt']));
				}
				if ($result['LostAt'] !== null) {
					$head->setLostAt(\DateTime::createFromFormat('U.u', $result['LostAt']));
				}
			} catch (\Exception $e) {
				throw new \LogicException("Cannot create datetime", 0, $e);
			}
		} finally {
			$statement->closeCursor();
		}
	}

	public function getItemsForAutosuggest(string $code, bool $secondTry = false): array
	{
		$limit = 10;
		if ($secondTry) {
			$limit *= 2;
			$value = "%$code%";
		} else {
			$value = "$code%";
		}

		$statement = $this->getPDO()
			->prepare(
				"SELECT Code
FROM Item
WHERE DeletedAt IS NULL AND Code LIKE :f
LIMIT $limit"
			);
		try {
			$statement->bindValue(':f', $value);
			$success = $statement->execute();
			assert($success, 'get items for autosuggest');
			$array = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
		} finally {
			$statement->closeCursor();
		}
		if (count($array) < $limit && !$secondTry) {
			$more = $this->getItemsForAutosuggest($code, true);
			$array = array_merge($array, array_diff($more, $array));
		}
		return $array;
	}

	public function getLocationsForAutosuggest(string $code, bool $secondTry = false): array
	{
		$limit = 10;
		if ($secondTry) {
			$limit *= 2;
			$value = "%$code%";
		} else {
			$value = "$code%";
		}

		$statement = $this->getPDO()
			->prepare(
				"SELECT t1.Code AS name, t2.ValueEnum AS color
FROM `ProductItemFeatureUnified` AS t1
LEFT JOIN `ItemFeature` AS t2
ON t2.Feature = 'color' AND t1.Code = t2.Code
WHERE t1.ValueEnum = 'location' AND t1.Code LIKE :f
LIMIT $limit"
			);
		try {
			$statement->bindValue(':f', $value);
			$success = $statement->execute();
			assert($success, 'get locations for autosuggest');
			$array = $statement->fetchAll(\PDO::FETCH_ASSOC);
			//$colors = $statement->fetchAll(\PDO::FETCH_COLUMN, 1);
		} finally {
			$statement->closeCursor();
		}
		if (count($array) < $limit && !$secondTry) {
			$more = $this->getLocationsForAutosuggest($code, true);
			$loc = array_map(function ($l) {
				return $l["name"];
			}, $array);
			foreach ($more as $value) {
				if (!in_array($value["name"], $loc)) {
					array_push($array, $value);
				}
			}
		}
		return $array;
	}

	public function getTypesForItemCodes($list)
	{
		if (sizeof($list) == 0) return array();
		$pdo = $this->getPDO();
		$prefix = $itemsList = '';
		foreach ($list as $item)
		{
			$itemsList .= $prefix . $pdo->quote($item);
			$prefix = ', ';
		}
		$statement = $pdo->prepare(
			"SELECT Item.Code AS Code, ItemFeature.ValueEnum AS ItemValue, ProductFeature.ValueEnum AS ProductValue
FROM Item
LEFT JOIN ItemFeature ON Item.Code = ItemFeature.Code AND ItemFeature.Feature = 'type'
LEFT JOIN ProductFeature ON Item.Brand = ProductFeature.Brand AND Item.Model = ProductFeature.Model AND Item.Variant = ProductFeature.Variant AND ProductFeature.Feature = 'type'
WHERE Item.Code IN ($itemsList);"
		);
		$missingItems = array_merge(array(), $list);
		$output = array();
		try {
			$success = $statement->execute();
			$array = $statement->fetchAll(\PDO::FETCH_ASSOC);
			foreach($array as $thing) {
				if (isset($thing["ProductValue"]) || isset($thing["ItemValue"])) {
					$output[$thing["Code"]] = $thing["ItemValue"] ?? $thing["ProductValue"];
				} else {
					$output[$thing["Code"]] = null;
				}
				unset($missingItems[array_search($thing["Code"], $missingItems)]);
			}
			foreach($missingItems as $missing) {
				$output[$missing] = null;
			}
		} finally {
			$statement->closeCursor();
		}

		return $output;
	}

	public function checkItemListAllExist($list)
	{
		if (sizeof($list) == 0) return true;
		$pdo = $this->getPDO();
		$prefix = $itemsList = '';
		foreach ($list as $item)
		{
			$itemsList .= $prefix . $pdo->quote($item);
			$prefix = ', ';
		}
		$statement = $pdo->prepare(
			"SELECT COUNT(`Code`) AS Total FROM Item WHERE Code IN ($itemsList) AND `DeletedAt` IS NULL"
		);
		try {
			$success = $statement->execute();
			$result = $statement->fetch(\PDO::FETCH_ASSOC);
			if ($result["Total"] == sizeof($list)) return true;
			return false;
		} finally {
			$statement->closeCursor();
		}

		return $output;
	}
}
