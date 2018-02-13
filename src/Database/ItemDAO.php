<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemPrefixer;
use WEEEOpen\Tarallo\Server\ItemUpdate;
use WEEEOpen\Tarallo\Server\NotFoundException;

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

	private $getItemQuery = null;

	/**
	 * Get a single item (and its content)
	 *
	 * @param ItemIncomplete $item
	 * @param null $token
	 * @param int $depth
	 */
	public function getItem(ItemIncomplete $item, $token = null, $depth = 10) {
		if($token !== null && !$this->checkToken($item, $token)) {
			throw new NotFoundException();
		}

		if(!is_int($depth)) {
			throw new \InvalidArgumentException('Depth must be an integer, ' . gettype($token) . ' given');
		}

		if($this->getItemQuery === null) {
			$this->getItemQuery = $this->getPDO()->prepare(<<<EOQ
				SELECT `Code`, `Brand`, `Model`, `Variant`, `Movable`, Ancestor AS Parent
				FROM Tree
				JOIN Item ON Descendant=`Code`
				WHERE Descendant IN (
					SELECT DISTINCT Descendant
					FROM Tree
					WHERE Ancestor = ?
					AND Depth < ?
					ORDER BY Depth
				)
				AND Depth = 1
EOQ
			);
		}

		$this->getItemQuery->execute([$item->getCode(), $depth + 1]);

		if(($row = $this->getItemQuery->fetch(\PDO::FETCH_ASSOC)) === false) {
			throw new NotFoundException();
		}

		$flat = [];

		$flat[] = $head = new Item($row['Code']);

		$this->fillItem($head, $row['Brand'], $row['Model'], $row['Variant'], $row['Movable']);
		$head->addAncestors($this->database->treeDAO()->getPathTo($head));

		while(($row = $this->getItemQuery->fetch(\PDO::FETCH_ASSOC)) !== false) {
			if(!isset($flat[$row['Parent']])) {
				throw new \LogicException('Broken tree: got ' . $row['Code'] . ' before its parent ' . $row['Parent']);
			}
			$this->fillItem(new Item($row['Code']), $row['Brand'], $row['Model'], $row['Variant'], $row['Movable'], $flat[$row['Parent']]);
		}
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
		if(!is_bool($result[0])) {
			throw new \LogicException('Result is not boolean');
		}
		if($result[0] === true) {
			return true;
		} else {
			return false;
		}
	}

	private function fillItem(Item $item, $brand, $model, $variant, $movable, Item $parent = null) {
		$brand === null ?: $item->addFeature(new Feature('brand', $brand));
		$model === null ?: $item->addFeature(new Feature('model', $model));
		// TODO: these shouldn't be plain features... also, don't discard $variant
		$movable === null ?: $item->addFeature(new Feature('soldered-in-place', $movable ? 'no' : 'yes'));

		if($parent !== null) {
			$parent->addContent($item);
		}
	}

	private function setItemDefaults(ItemUpdate $item) {
		// TODO: reimplement
	}
}
