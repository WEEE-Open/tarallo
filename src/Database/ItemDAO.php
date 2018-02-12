<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\InvalidParameterException;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemPrefixer;
use WEEEOpen\Tarallo\Server\ItemUpdate;

final class ItemDAO extends DAO {
	public function addItems(array $items, ItemIncomplete $parent = null) {
		if(empty($items)) {
			return;
		}

		$this->database->beginTransaction();

		try {
			foreach($items as $item) {
				/**
				 * @var Item $item
				 */
				$this->addItem($item, $parent);
			}
		} catch(\Throwable $e) {
			$this->database->rollback();
			throw $e;
		}
		$this->database->commit();
	}

	private $addItemStatement = null;

	/**
	 * Insert a single item into the database, return its id. Basically just add a row to Item, no features are added.
	 * Must be called while in transaction.
	 *
	 * @param Item $item the item to be inserted
	 * @param ItemIncomplete $parent parent item
	 *
	 * @TODO: is parent still necessary? Is it a good design? Can't inner items have a full "location"?
	 *
	 * @see addItems
	 */
	private function addItem(Item $item, ItemIncomplete $parent = null) {
		if(!($item instanceof Item)) {
			// will say "object" if it's another object which is kinda useless, whatever
			throw new \InvalidArgumentException('Items must be objects of Item class, ' . gettype($item) . ' given');
		}

		$pdo = $this->getPDO();
		if(!$pdo->inTransaction()) {
			throw new \LogicException('addItem called outside of transaction');
		}

		if(!$item->hasCode()) {
			// TODO: use getNewCode only for "head" items and use directly in query for inner ones? Then query database to get all inner items again (so they aren't left un-updated)?
			// That also makes easier to insert items with a known parent but unknown full list of ancestors (got the code, query everything again, done!)
			$prefix = ItemPrefixer::get($item);
			$code = $this->getNewCode($prefix);
			$item->setCode($code);
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

	private $getItemQuery = null;

	/**
	 * Get a single item (and its content)
	 *
	 * @param ItemIncomplete $item
	 * @param null $token
	 * @param int $depth
	 */
	public function getItem(ItemIncomplete $item, $token = null, $depth = 10) {
		if($this->getItemQuery === null) {
			$this->getItemQuery = $this->getPDO()->prepare(<<<EOQ
				SELECT DescendantItem.Code, AncestorItem.Code AS ImmediateParent
				FROM Tree
				JOIN Item AS AncestorItem ON AncestorID=AncestorItem.ItemID
				JOIN Item AS DescendantItem ON DescendantID=DescendantItem.ItemID
				WHERE DescendantID IN (
				SELECT DISTINCT DescendantID
				FROM Tree
				WHERE AncestorID = (
				SELECT ItemID
				FROM Item
				WHERE Code = 'ZonaBlu'
				)
				ORDER BY Depth
				)
				AND Depth = 1
EOQ
			);
		}
	}

	private function setItemDefaults(ItemUpdate $item) {
		// TODO: reimplement
	}
}
