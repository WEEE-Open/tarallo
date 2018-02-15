<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemPrefixer;
use WEEEOpen\Tarallo\Server\ItemUpdate;
use WEEEOpen\Tarallo\Server\NotFoundException;

final class ItemDAO extends DAO {
	/**
	 * Add all items. Also starts and finishes the necessary transaction.
	 *
	 * @param array $items Items to add
	 * @param ItemIncomplete|null $parent Parent item for all these
	 *
	 * @return Item[] All inserted items, in same order, retrieved from database. Array keys are preserved.
	 * @throws \Throwable whatever was thrown inside, PHPStorm is forcind me to add a comment
	 *
	 * @see addItem to add one item (and manage transaction yourself)
	 */
	public function addItems(array $items, ItemIncomplete $parent = null) {
		if(empty($items)) {
			return [];
		}

		$heads = [];

		try {
			$this->database->beginTransaction();
			foreach($items as $k => $item) {
				$heads[$k] = $this->addItem($item, $parent);
			}
			$this->database->commit();
		} catch(\Throwable $e) {
			$this->database->rollback();
			throw $e;
		}

		return $heads;
	}

	private $addItemStatement = null;

	/**
	 * Insert a single item into the database, return its id. Basically just add a row to Item, no features are added.
	 * Must be called while in transaction.
	 *
	 * @param Item $item the item to be inserted
	 * @param ItemIncomplete $parent parent item
	 *
	 * @return Item Same item, retrieved from database
	 *
	 * @see addItems
	 */
	public function addItem(Item $item, ItemIncomplete $parent = null) {
		if($parent === null) {
			return $this->addItemInternal($item);
		} else {
			return $this->addItemInternal($item, $parent);
		}
	}

	/**
	 * @see addItem
	 *
	 * @param Item $item the item to be inserted
	 * @param ItemIncomplete $parent parent item
	 * @param bool $last leave it as it is
	 *
	 * @return null|Item Outer call always returns Item, internal ones (it's recursive) return null
	 */
	public function addItemInternal(Item $item, ItemIncomplete $parent = null, $last = true) {
		$pdo = $this->getPDO();
		if(!$pdo->inTransaction()) {
			throw new \LogicException('addItem called outside of transaction');
		}

		if(!$item->hasCode()) {
			$prefix = ItemPrefixer::get($item);
			$code = $this->getNewCode($prefix);
			$item->setCode($code);
		}

		if($this->addItemStatement === null) {
			// TODO: add brand, model, variant
			$this->addItemStatement = $pdo->prepare('INSERT INTO Item (`Code`, Token) VALUES (:cod, :tok)');
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
			$this->addItemInternal($childItem, $item, false);
		}

		if($last) {
			return $this->getItem($item, null);
		} else {
			return null;
		}
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
			$this->getNewCodeStatement = $this->getPDO()->prepare('SELECT GenerateCode(?)');
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
	 * @param ItemIncomplete $item
	 * @param string|null $token
	 * @param int $depth max depth
	 *
	 * @return Item
	 */
	public function getItem(ItemIncomplete $item, $token = null, $depth = 10) {
		if($token !== null && !$this->checkToken($item, $token)) {
			throw new NotFoundException();
		}

		if(!is_int($depth)) {
			throw new \InvalidArgumentException('Depth must be an integer, ' . gettype($token) . ' given');
		}

		if($this->getItemStatement === null) {
			// TODO: we can also select Depth here, may be useful to select depth = maxdepth + 1 and see if there are items inside and discard them, but it's slow and unefficient...
			$this->getItemStatement = $this->getPDO()->prepare(<<<EOQ
				SELECT `Code`, `Brand`, `Model`, `Variant`, GetParent(`Code`) AS Parent
				FROM Item
				JOIN Tree ON Descendant=`Code` 
				WHERE Ancestor = ?
				AND Depth < ?
				ORDER BY Depth
EOQ
			);
		}

		/**
		 * All items in this subtree, flattened
		 */
		$flat = [];

		$head = new Item($item->getCode());
		$item = null;

		try {
			$this->getItemStatement->execute([$head->getCode(), $depth]);

			// First Item is the head Item
			if(($row = $this->getItemStatement->fetch(\PDO::FETCH_ASSOC)) === false) {
				throw new NotFoundException();
			}

			$this->fillItem($head, $row['Brand'], $row['Model'], $row['Variant']);
			$flat[$head->getCode()] = $head;

			// Other items
			while(($row = $this->getItemStatement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$parent = $flat[$row['Parent']];
				if(!isset($parent)) {
					throw new \LogicException('Broken tree: got ' . $row['Code'] . ' before its parent ' . $row['Parent']);
				}
				$current = $flat[$row['Code']] = new Item($row['Code']);
				$this->fillItem($current, $row['Brand'], $row['Model'], $row['Variant'], $parent);
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

		$tokenquery->execute([$item->getCode(), $token]);
		$result = $tokenquery->fetch(\PDO::FETCH_NUM);
		// MySQL doesn't understand booleans, they're just tinyints, and we get that lack of abstraction slapped right on our face because yes.
		$exists = (bool) $result[0];

		return $exists;
	}

	private function fillItem(Item $item, $brand, $model, $variant, Item $parent = null) {
		$brand === null ?: $item->addFeature(new Feature('brand', $brand));
		$model === null ?: $item->addFeature(new Feature('model', $model));
		// TODO: these shouldn't be plain features... also, don't discard $variant

		if($parent !== null) {
			$parent->addContent($item);
		}
	}

	private function setItemDefaults(ItemUpdate $item) {
		// TODO: reimplement
	}
}
