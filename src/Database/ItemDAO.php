<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\InvalidParameterException;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemUpdate;

final class ItemDAO extends DAO {
	public function addItems($items, ItemIncomplete $parent = null) {
		if($items instanceof Item) {
			$items = [$items];
		} else if(!is_array($items)) {
			throw new \InvalidArgumentException('Items must be passed as an array or a single Item');
		}

		if(empty($items)) {
			return;
		}

		foreach($items as $item) {
			$this->addItem($item, $parent);
		}

		return;
	}

	private $addItemStatement = null;

	/**
	 * Insert a single item into the database, return its id. Basically just add a row to Item, no features are added.
	 * Must be called while in transaction.
	 *
	 * @param Item $item the item to be inserted
	 * @param ItemIncomplete $parent parent item
	 *
	 * @see addItems
	 */
	private function addItem(Item $item, ItemIncomplete $parent = null) {
		if(!($item instanceof Item)) {
			throw new \InvalidArgumentException('Items must be objects of Item class, ' . gettype($item) . ' given'); // will say "object" if it's another object which is kinda useless, whatever
		}

		$pdo = $this->getPDO();
		if(!$pdo->inTransaction()) {
			throw new \LogicException('addItem called outside of transaction');
		}

		if($this->addItemStatement === null) {
			$this->addItemStatement = $pdo->prepare('INSERT INTO Item (`Code`, IsDefault, `Default`) VALUES (:c, :isd, :def)');
		}

		$this->addItemStatement->bindValue(':c', $item->getCode(), \PDO::PARAM_STR);
		$this->addItemStatement->bindValue(':isd', 0, \PDO::PARAM_INT);
		$this->addItemStatement->bindValue(':def', null, \PDO::PARAM_NULL); // TODO: remove this stuff
		$this->addItemStatement->execute();

		/** @var Item $item */
		$this->database->featureDAO()->addFeatures($item);
		$this->database->treeDAO()->addToTree($item, $parent);

		$childItems = $item->getContents();
		foreach($childItems as $childItem) {
			// yay recursion!
			$this->addItem($childItem, $item);
		}
	}

	private $getItemIdStatement = null;
	private $getItemIdCache = [];

	public function getItemId(ItemIncomplete $item) {
		$code = $item->getCode();
		if(isset($this->getItemIdCache[$code])) {
			// let's just HOPE this thing doesn't blow up catastrophically.
			return $this->getItemIdCache[$code];
		}

		if($this->getItemIdStatement === null) {
			$this->getItemIdStatement = $this->getPDO()->prepare('SELECT ItemID FROM Item WHERE `Code` = ? LIMIT 1');
		}

		$this->getItemIdStatement->execute([$code]);
		if($this->getItemIdStatement->rowCount() === 0) {
			$this->getItemIdStatement->closeCursor();
			throw new InvalidParameterException('Unknown item ' . $item->getCode());
		} else {
			$id = (int) $this->getItemIdStatement->fetch(\PDO::FETCH_NUM)[0];
			$this->getItemIdStatement->closeCursor();
			$this->getItemIdCache[$code] = $id;
		}

		return $id;
	}


	public function updateItems($items) {
		if($items instanceof ItemUpdate) {
			$items = [$items];
		} else if(!is_array($items)) {
			throw new \InvalidArgumentException('Items to be updated must be passed as an array or a single ItemUpdated, ' . gettype($items) . ' given');
		}

		if(empty($items)) {
			return;
		}

		foreach($items as $item) {
			if(!($item instanceof ItemUpdate)) {
				throw new \InvalidArgumentException('Items to be updated must be ItemUpdate objects');
			}
			/** @var ItemUpdate $item */
			if($item->getDefaultCodeChanged() || $item->getIsDefaultChanged()) {
				$this->setItemDefaults($item);
			}
			if($item->getParentChanged()) {
				$this->database->treeDAO()->moveItem($item, $item->getParent());
			}
			$this->database->featureDAO()->updateDeleteFeatures($item);
		}
	}

	private function setItemDefaults(ItemUpdate $item) {
		// TODO: reimplement
	}

	public function getCodes() {

	}

	private $getNextCodeStatement = null;
	private $setNextCodeStatement = null;
	private $lockTablesStatement = null;

	/**
	 * Locks Codes table and begins a transaction.
	 * Locking it avoids generating the same code twice.
	 *
	 * Codes table is used only when creating new items, so its performance isn't really that critical.
	 * However (My)SQL doesn't have a way to lock writes but allow reads from a table, a table can be READ locked
	 * (= "allow me to read") or WRITE locked (= "allow me to write, don't allow anybody else to read), so Item has to
	 * be locked too. WIRTE locked. This would completely nuke performance, so it isn't done.
	 * Because of this, adding items may fail randomly if someone else is simultaneously adding items with manually-set
	 * codes that collide with generated ones.
	 */
	private function beginNextCodeTransaction() {
		$this->getPDO()->beginTransaction();
		if($this->lockTablesStatement === null) {
			$this->lockTablesStatement = $this->getPDO()->prepare("LOCK TABLE Codes WRITE");
		}
		$locked = $this->lockTablesStatement->execute();
		$this->lockTablesStatement->closeCursor();
		if(!$locked) {
			throw new \Exception('Cannot generate code (cannot lock Codes table)');
		}
	}

	/**
	 * Commit stuff, but don't unlock tables, that will be done by the next BEGIN TRANSACTION
	 */
	private function endNextCodeTransaction() {
		$committed = $this->getPDO()->commit();
		if(!$committed) {
			throw new \Exception('Failed updating code counters (transaction not committed)');
		}
	}

	/**
	 * Get automatically-generated codes, given a prefix.
	 * Null or empty string means "no prefix".
	 *
	 * @param $forWhat array - List of prefixes
	 *
	 * @return array - same array as input, but with each prefix replaced by its generated code
	 */
	public function getNextCodes($forWhat) {
		if(!is_array($forWhat)) {
			throw new \InvalidArgumentException('Expecting an array of prefixes, ' . gettype($forWhat) . ' given');
		}
		if(empty($forWhat)) {
			return [];
		}

		$codes = [];

		// TODO: determine why every conceivable SELECT query can happily run, while LOCK TABLE fails because of buffered results (that don't actually exist)
		//$this->beginNextCodeTransaction();

		foreach($forWhat as $key => $prefix) {
			if($prefix === null) {
				$prefix = '';
			} else if(!is_string($prefix)) {
				throw new \InvalidArgumentException('Prefix must be a string, ' . gettype($prefix) . ' given');
			}
			$codes[$key] = $this->getNextCode($prefix);
		}

		//$this->endNextCodeTransaction();

		return $codes;
	}

	/**
	 * Get next available code with specified prefix.
	 *
	 * @param string $prefix
	 *
	 * @return string next available code, prefix included
	 * @see beginNextCodeTransaction
	 * @see endNextCodeTransaction
	 */
	private function getNextCode($prefix = '') {
		// TODO: increment and get, instead of get then increment?
		if($this->getNextCodeStatement === null) {
			$this->getNextCodeStatement = $this->getPDO()->prepare('SELECT `Integer` FROM Codes WHERE Prefix = ?');
		}
		$this->getNextCodeStatement->bindValue(1, $prefix, \PDO::PARAM_STR);
		$this->getNextCodeStatement->execute();
		if($this->getNextCodeStatement->rowCount() > 0) {
			$result = $this->getNextCodeStatement->fetchAll(\PDO::FETCH_ASSOC)[0];
			$this->getNextCodeStatement->closeCursor();
			$integer = (int) $result['Integer'];
		} else {
			$this->getNextCodeStatement->closeCursor();
			throw new \InvalidArgumentException('No counter found in database for code prefix "' . $prefix . '"');
		}

		// Integer should be last used one, so increment it before checking if it's available.
		// Checking is necessary since there could be items with manually-assigned codes.
		do {
			$integer++;
			$exists = $this->checkIfItemExists($prefix . $integer);
		} while($exists);

		if($this->setNextCodeStatement === null) {
			$this->setNextCodeStatement = $this->getPDO()->prepare('UPDATE Codes SET `Integer` = ? WHERE Prefix = ?');
		}
		// Integer is now taken, save it into Codes, next time it will be incremented before checking if it's available
		$this->setNextCodeStatement->bindValue(1, $integer, \PDO::PARAM_INT);
		$this->setNextCodeStatement->bindValue(2, $prefix, \PDO::PARAM_STR);
		$this->setNextCodeStatement->execute();

		return $prefix . $integer;
	}

	private $checkIfItemExistsStatement = null;

	/**
	 * Check if an item with a specified code already exists.
	 *
	 * @param string $code
	 *
	 * @return bool true if it exists, false otherwise
	 */
	private function checkIfItemExists($code) {
		if($this->checkIfItemExistsStatement === null) {
			$this->checkIfItemExistsStatement = $this->getPDO()->prepare('SELECT COUNT(*) AS c FROM Item WHERE `Code` = ?');
		}

		$this->checkIfItemExistsStatement->bindValue(1, $code, \PDO::PARAM_STR);
		$this->checkIfItemExistsStatement->execute();
		$result = $this->checkIfItemExistsStatement->fetchAll(\PDO::FETCH_ASSOC)[0];
		$this->checkIfItemExistsStatement->closeCursor();

		return ((int) $result['c']) > 0;
	}
}
