<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemPrefixer;
use WEEEOpen\Tarallo\Server\NotFoundException;

final class ItemDAO extends DAO {
	const EXCEPTION_CODE_GENERATE_ID = 3;

	/**
	 * Insert a single item into the database
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

		$statement = $this->getPDO()->prepare('INSERT INTO Item (`Code`, Token) VALUES (:cod, :tok)');

		try {
			$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);
			$statement->bindValue(':tok', $item->token, \PDO::PARAM_STR);
			if(!$statement->execute()) {
				throw new DatabaseException('Cannot insert item ' . $item->getCode() . ' for unknown reasons');
			}
		} finally {
			$statement->closeCursor();
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

	public function deleteItem(ItemIncomplete $item) {
		if(!$this->itemVisible($item)) {
			throw new NotFoundException();
		}

		$statement = $this->getPDO()->prepare('UPDATE Item SET DeletedAt = NOW() WHERE `Code` = ?');

		try {
			$statement->execute([$item->getCode()]);
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Yes, it's kinda like the true/false/FileNotFound thing.
	 *
	 * @param ItemIncomplete $item
	 *
	 * @return bool|null tri-state: true if marked as deleted, false if not marked but exists, null if doesn't exist
	 */
	private function itemIsDeleted(ItemIncomplete $item) {
		$statement = $this->getPDO()->prepare('SELECT IF(DeletedAt IS NULL, FALSE, TRUE) FROM Item WHERE `Code` = :cod');
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
	 * @param ItemIncomplete $item
	 *
	 * @return bool
	 */
	public function itemExists(ItemIncomplete $item) {
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
	 * @param ItemIncomplete $item
	 *
	 * @return bool
	 * @see itemExists to check wether item exists at all or not
	 */
	public function itemVisible(ItemIncomplete $item) {
		$deleted = $this->itemIsDeleted($item);
		if($deleted === false) {
			return true;
		}

		return false;
	}

	/**
	 * Get a new sequential code directly from database, for a given prefix
	 *
	 * @param string $prefix
	 *
	 * @return string
	 */
	private function getNewCode($prefix) {
		$statement = $this->getPDO()->prepare(/** @lang MySQL */
			'SELECT GenerateCode(?)');
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
	 * @param ItemIncomplete $itemToGet
	 * @param string|null $token
	 * @param int|null $depth max depth
	 *
	 * @return Item
	 */
	public function getItem(ItemIncomplete $itemToGet, $token = null, int $depth = null) {
		if($token !== null && !$this->checkToken($itemToGet, $token)) {
			throw new NotFoundException();
		}

		if($depth === null) {
			$depth = 10;
		}

		// TODO: we can also select Depth here, may be useful to select depth = maxdepth + 1 and see if there are items inside and discard them, but it's slow and unefficient...
		$statement = $this->getPDO()->prepare(<<<EOQ
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
			if(!$statement->execute([$itemToGet->getCode(), $depth])) {
				throw new DatabaseException('Query failed for no reason');
			}

			// First Item is the head Item
			if(($row = $statement->fetch(\PDO::FETCH_ASSOC)) === false) {
				throw new NotFoundException();
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
					throw new \LogicException('Broken tree: got ' . $row['Code'] . ' before its parent ' . $row['Parent']);
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
