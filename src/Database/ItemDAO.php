<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemPrefixer;
use WEEEOpen\Tarallo\Server\NotFoundException;

final class ItemDAO extends DAO {
	const EXCEPTION_CODE_GENERATE_ID = 3;

	private $addItemStatement = null;

	/**
	 * Insert a single item into the database, then returns it by querying the database again.
	 *
	 * @param Item $item the item to be inserted
	 * @param ItemIncomplete $parent parent item
	 */
	public function addItem(Item $item, ItemIncomplete $parent = null) {
		if(!$item->hasCode()) {
			try {
				$prefix = ItemPrefixer::get($item);
			} catch(\InvalidArgumentException $e) {
				throw new \InvalidArgumentException($e->getMessage(), self::EXCEPTION_CODE_GENERATE_ID);
			}
			$code = $this->getNewCode($prefix);
			$item->setCode($code);
		}

		if($this->addItemStatement === null) {
			$this->addItemStatement = $this->getPDO()->prepare('INSERT INTO Item (`Code`, Token) VALUES (:cod, :tok)');
		}

		try {
			$this->addItemStatement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);
			$this->addItemStatement->bindValue(':tok', $item->token, \PDO::PARAM_STR);
			if(!$this->addItemStatement->execute()) {
				throw new DatabaseException('Cannot insert item ' . $item->getCode() . ' for unknown reasons');
			}
		} finally {
			$this->addItemStatement->closeCursor();
		}

		/** @var Item $item */
		$this->database->featureDAO()->setFeatures($item);
		$this->database->treeDAO()->addToTree($item, $parent);

		$childItems = $item->getContents();
		foreach($childItems as $childItem) {
			// yay recursion!
			$this->addItem($childItem, $item);
		}
	}

	private $deleteItemStatement;

	public function deleteItem(ItemIncomplete $item) {
		if($this->deleteItemStatement === null) {
			$this->deleteItemStatement = $this->getPDO()->prepare('UPDATE Item SET DeletedAt = NOW() WHERE `Code` = ?');
		}

		$this->database->treeDAO()->removeFromTree($item);

		try {
			$this->deleteItemStatement->execute([$item->getCode()]);
		} finally {
			$this->deleteItemStatement->closeCursor();
		}
	}

	private $itemIsDeletedStatement = null;

	/**
	 * Yes, it's kinda like the true/false/FileNotFound thing.
	 *
	 * @param ItemIncomplete $item
	 *
	 * @return bool|null tri-state: true if marked as deleted, false if not marked but exists, null if doesn't exist
	 */
	private function itemIsDeleted(ItemIncomplete $item) {
		if($this->itemIsDeletedStatement === null) {
			$this->itemIsDeletedStatement = $this->getPDO()->prepare('SELECT IF(DeletedAt IS NULL, FALSE, TRUE) FROM Item WHERE `Code` = :cod');
		}
		try {
			$this->itemIsDeletedStatement->execute([$item->getCode()]);
			if($this->itemIsDeletedStatement->rowCount() === 0) {
				return null;
			}
			$result = $this->itemIsDeletedStatement->fetch(\PDO::FETCH_NUM);
			$exists = (bool) $result[0];

			return $exists;

		} finally {
			$this->itemIsDeletedStatement->closeCursor();
		}
	}

	/**
	 * True If item exists and is not marked as deleted, false otherwise.
	 *
	 * @param ItemIncomplete $item
	 *
	 * @return bool
	 * @throws NotFoundException If item doesn't exist in any form anywhere
	 */
	public function itemAvailable(ItemIncomplete $item) {
		$deleted = $this->itemIsDeleted($item);
		if($deleted === false) {
			return true;
		}

		return false;
	}

	/**
	 * True if item exists in the database and is marked as deleted, false otherwise
	 *
	 * @param ItemIncomplete $item
	 *
	 * @return bool
	 * @see itemAvailable to check wether item is available or deleted (call only that method, not both!)
	 */
	public function itemRecoverable(ItemIncomplete $item) {
		$deleted = $this->itemIsDeleted($item);
		if($deleted === true) {
			return true;
		}

		return false;
	}

	private $getNewCodeStatement = null;

	/**
	 * Get a new sequential code directly from database, for a given prefix
	 *
	 * @param string $prefix
	 *
	 * @return string
	 */
	private function getNewCode($prefix) {
		if($this->getNewCodeStatement === null) {
			$this->getNewCodeStatement = $this->getPDO()->prepare(/** @lang MySQL */
				'SELECT GenerateCode(?)');
		}
		try {
			$this->getNewCodeStatement->execute([$prefix]);
			$code = $this->getNewCodeStatement->fetch(\PDO::FETCH_NUM)[0];
			if($code === null) {
				throw new \LogicException("Cannot generate code for prefix $prefix, NULL returned");
			}

			return (string) $code;
		} finally {
			$this->getNewCodeStatement->closeCursor();
		}
	}

	private $getItemStatement = null;

	/**
	 * Get a single item (and its content)
	 *
	 * @param ItemIncomplete $itemToGet
	 * @param string|null $token
	 * @param int $depth max depth
	 *
	 * @return Item
	 */
	public function getItem(ItemIncomplete $itemToGet, $token = null, $depth = 10) {
		if($token !== null && !$this->checkToken($itemToGet, $token)) {
			throw new NotFoundException();
		}

		if(!is_int($depth)) {
			throw new \InvalidArgumentException('Depth must be an integer, ' . gettype($token) . ' given');
		}

		if($this->getItemStatement === null) {
			// TODO: we can also select Depth here, may be useful to select depth = maxdepth + 1 and see if there are items inside and discard them, but it's slow and unefficient...
			$this->getItemStatement = $this->getPDO()->prepare(<<<EOQ
				SELECT `Descendant` AS `Code`, GetParent(`Descendant`) AS Parent
				FROM Tree
				WHERE Ancestor = ?
				AND Depth < ?
				ORDER BY Depth ASC, Code ASC
EOQ
			);
		}

		/**
		 * All items in this subtree, flattened
		 */
		$flat = [];

		try {
			if(!$this->getItemStatement->execute([$itemToGet->getCode(), $depth])) {
				throw new DatabaseException('Query failed for no reason');
			}

			// First Item is the head Item
			if(($row = $this->getItemStatement->fetch(\PDO::FETCH_ASSOC)) === false) {
				throw new NotFoundException();
			} else {
				// Now we have the real code, with correct case (database is case-insensitive)
				$head = new Item($row['Code']);
				$flat[$head->getCode()] = $head;
				$itemToGet = null;
			}

			// Other items
			while(($row = $this->getItemStatement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$parent = $flat[$row['Parent']];
				if(!isset($parent)) {
					throw new \LogicException('Broken tree: got ' . $row['Code'] . ' before its parent ' . $row['Parent']);
				}
				$current = $flat[$row['Code']] = new Item($row['Code']);
				$parent->addContent($current);
			}
			$parent = null;
			unset($parent);
		} finally {
			$this->getItemStatement->closeCursor();
		}
		$this->database->treeDAO()->getPathTo($head);
		$this->database->featureDAO()->getFeaturesAll($flat);

		return $head;
	}

	/**
	 * Check that item can be obtained with a token.
	 *
	 * @param ItemIncomplete $item
	 * @param string $token
	 *
	 * @return bool true if possible, false if wrong token or item doesn't exist
	 */
	private function checkToken(ItemIncomplete $item, $token) {
		if(!is_string($token)) {
			throw new \InvalidArgumentException('Token must be a string, ' . gettype($token) . ' given');
		}

		$tokenquery = $this->getPDO()->prepare(<<<EOQ
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
}
