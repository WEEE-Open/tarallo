<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemPrefixer;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\ValidationException;

final class ItemDAO extends DAO {
	/**
	 * Insert a single item into the database
	 *
	 * @param ItemWithFeatures $item the item to be inserted
	 * @param ItemWithCode $parent parent item
	 *
	 * @throws DuplicateItemCodeException If any item with same code already exists
	 */
	public function addItem(ItemWithFeatures $item, ItemWithCode $parent = null) {
		assert($item instanceof ItemIncomplete || $item instanceof Item);
		if(!$item->hasCode()) {
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
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
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
		foreach($childItems as $childItem) {
			// yay recursion!
			$this->addItem($childItem, $item);
		}
	}

	/**
	 * Soft-delete an item (mark as deleted to make invisible, detach from tree)
	 *
	 * @param ItemWithCode $item
	 *
	 * @throws ValidationException if item contains other items (cannot be deleted)
	 */
	public function deleteItem(ItemWithCode $item) {
		$this->itemMustExist($item);
		$statement = $this->getPDO()->prepare('UPDATE Item SET DeletedAt = NOW() WHERE `Code` = ?');

		try {
			$statement->execute([$item->getCode()]);
		} catch(\PDOException $e) {
			if($e->getCode() === '45000' && $statement->errorInfo()[2] === 'Cannot delete an item while contains other items') {
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
	public function loseItem(ItemWithCode $item) {
		$this->itemMustExist($item);
		$statement = $this->getPDO()->prepare('UPDATE Item SET LostAt = NOW() WHERE `Code` = ?');

		try {
			$statement->execute([$item->getCode()]);
		} catch(\PDOException $e) {
			if($e->getCode() === '45000'
				&& $statement->errorInfo()[2] === 'Cannot mark an item as lost while it contains other items') {
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
	 *@deprecated This just doesn't cut it anymore with lost items
	 */
	private function itemIsDeleted(ItemWithCode $item) {
		$statement = $this->getPDO()
			->prepare('SELECT IF(DeletedAt IS NULL, FALSE, TRUE) FROM Item WHERE `Code` = :cod');
		try {
			$statement->execute([$item->getCode()]);
			if($statement->rowCount() === 0) {
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
	public function itemExists(ItemWithCode $item): bool {
		$deleted = $this->itemIsDeleted($item);
		if($deleted === null) {
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
	public function itemVisible(ItemWithCode $item): bool {
		$deleted = $this->itemIsDeleted($item);
		if($deleted === false) {
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
	public function itemMustExist(ItemWithCode $item, $allowDeleted = false) {
		// Use Item instead of ProductItemFeatures because we need to check DeletedAt, and we will modify Item anyway
		if($allowDeleted) {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod FOR UPDATE');
		} else {
			$statement = $this->getPDO()
				->prepare('SELECT `Code` FROM Item WHERE `Code` = :cod and `DeletedAt` IS NULL FOR UPDATE');
		}
		try {
			$statement->execute([$item->getCode()]);
			if($statement->rowCount() === 0) {
				throw new NotFoundException();
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
	public function itemDeletedAt(ItemWithCode $item) {
		$statement = $this->getPDO()->prepare('SELECT DeletedAt FROM Item WHERE `Code` = ?');
		try {
			$statement->execute([$item->getCode()]);
			if($statement->rowCount() === 0) {
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
	public function undelete(ItemWithCode $item) {
		$statement = $this->getPDO()->prepare('UPDATE Item SET DeletedAt = NULL WHERE `Code` = ?');
		try {
			$statement->execute([$item->getCode()]);
			if($statement->rowCount() === 0) {
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
	public function unlose(ItemWithCode $item) {
		$statement = $this->getPDO()->prepare('UPDATE Item SET LostAt = NULL WHERE `Code` = ?');
		try {
			$statement->execute([$item->getCode()]);
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
	private function getNewCode($prefix) {
		$statement = $this->getPDO()->prepare(
		/** @lang MySQL */
			'SELECT GenerateCode(?)'
		);
		try {
			$statement->execute([$prefix]);
			$code = $statement->fetch(\PDO::FETCH_NUM)[0];
			if($code === null) {
				throw new \LogicException("Cannot generate code for prefix $prefix, NULL returned");
			}

			return (string) $code;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get a single item (and its content)
	 *
	 * @param ItemWithCode $itemToGet
	 * @param string|null $token
	 * @param int|null $depth max depth
	 *
	 * @return Item
	 */
	public function getItem(ItemWithCode $itemToGet, $token = null, int $depth = null) {
		if($token !== null && !$this->checkToken($itemToGet, $token)) {
			throw new NotFoundException($itemToGet->peekCode());
		}

		if($depth === null) {
			$depth = 10;
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
			if(($row = $statement->fetch(\PDO::FETCH_ASSOC)) === false) {
				throw new NotFoundException($itemToGet->getCode());
			} else {
				// Now we have the real code, with correct case (database is case-insensitive)
				$head = new Item($row['Code']);
				$flat[$head->getCode()] = $head;
				$itemToGet = null;
			}

			// Other items
			while(($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$parent = $flat[$row['Parent']];
				if(!isset($parent)) {
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
	private function checkToken(ItemWithCode $item, string $token) {
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
	private function getExtraData(Item &$head) {
		// TODO: race conditions with other queries?
		$statement = $this->getPDO()
			->prepare(
				'SELECT UNIX_TIMESTAMP(DeletedAt) AS DeletedAt, UNIX_TIMESTAMP(LostAt) AS LostAt FROM Item WHERE `Code` = ?'
			);
		try {
			$statement->execute([$head->getCode()]);
			if($statement->rowCount() === 0) {
				throw new \LogicException('Item disappeared during a query');
			}
			$result = $statement->fetch(\PDO::FETCH_ASSOC);
			try {
				if($result['DeletedAt'] !== null) {
					$head->setDeletedAt(\DateTime::createFromFormat('U.u', $result['DeletedAt']));
				}
				if($result['LostAt'] !== null) {
					$head->setLostAt(\DateTime::createFromFormat('U.u', $result['LostAt']));
				}
			} catch(\Exception $e) {
				throw new \LogicException("Cannot create datetime", 0, $e);
			}
		} finally {
			$statement->closeCursor();
		}

	}
}
