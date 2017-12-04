<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemDefault;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemUpdate;
use WEEEOpen\Tarallo\Query\SearchTriplet;

final class ItemDAO extends DAO {
	private function depthSanitize($depth) {
		if(is_numeric($depth)) {
			return (int) $depth;
		} else {
			return 10;
		}
	}

	/**
	 * Prepare "code" part of query.
	 *
	 * @param array $codes
	 *
	 * @return string
	 */
	private function codePrepare($codes) {
		if(self::isArrayAndFull($codes)) {
			$codeWhere = '`Code` IN (' . $this->multipleIn(':code', $codes);
			return $codeWhere . ')';
		} else {
			return '';
		}
	}

	private function tokenPrepare($token) {
		if(is_string($token) && $token !== null) {
			return 'Token = :token';
		} else {
			return '';
		}
	}

	/**
	 * Get the ABNORME search subquery.
	 * Bind :searchkey0, :searchkey1, ... to keys and :searchvalue0, :searchvalue1, ... to values.
	 *
	 * @param $searches array of SearchTriplet
	 *
	 * @return string piece of query string
	 * @see FeatureDAO::getWhereStringFromSearches
	 */
	private function searchPrepare($searches) {
		if(!self::isArrayAndFull($searches)) {
			throw new \InvalidArgumentException('Search parameters must be passed as a non-empty array');
		}

		$subquery = '';
		$wheres = $this->database->featureDAO()->getWhereStringFromSearches($searches);
		if(count($wheres) <= 0) {
			throw new \LogicException('getWhereStringFromSearches() did not return anything, but there were ' . count($searches) . ' search parameters');
		}
		$wheresdefault = $wheres;
		foreach($wheresdefault as $k => $where) {
			$wheresdefault[$k] = str_replace(':searchname', ':searchnamedefault', $wheresdefault[$k]);
			$wheresdefault[$k] = str_replace(':searchvalue', ':searchvaluedefault', $wheresdefault[$k]);
		}
		foreach($wheres as $k => $where) {
			//			$subquery .= '
			//			AND (
			//				ItemID IN (SELECT ItemID ' . $where . ')
			//				OR (
			//					Default IS NOT NULL
			//					AND Default IN (SELECT ItemID ' . $where . ')
			//				)
			//			)';
			$subquery .= '
			AND (
				ItemID IN (SELECT Item.ItemID ' . $where . ')
				OR
				Item.`Default` IN (SELECT Item.ItemID ' . $wheresdefault[$k] . ')
			)';
		}

		$query = '
		ItemID IN (
			SELECT ItemID
			FROM Item
			WHERE IsDefault = 0
			' . $subquery . '
		)
		';

		return $query;
	}

	/**
	 * Place strings here, place a WHERE in front and AND between them.
	 *
	 * @return string result or empty string if supplied strings where empty, too
	 */
	private static function implodeOptionalWhereAnd() {
		$args = func_get_args();
		$where = self::implodeAnd($args);
		if($where === '') {
			return '';
		} else {
			return 'WHERE ' . $where;
		}
	}

	/**
	 * Join non-empty string arguments via " AND " to add in a WHERE clause.
	 *
	 * @see implodeOptionalWhereAnd
	 *
	 * @param $args string[]
	 *
	 * @return string string separated by AND, or empty string if supplied strings where empty
	 */
	private static function implodeAnd($args) {
		$stuff = [];
		foreach($args as $arg) {
			if(is_string($arg) && strlen($arg) > 0) {
				$stuff[] = $arg;
			}
		}
		$c = count($stuff);
		if($c === 0) {
			return '';
		}

		return implode(' AND ', $stuff);
	}

	/**
	 * Go get those items!
	 *
	 * @param $codes string[]|null Directly select those items (and filter with other parameters)
	 * @param $searches SearchTriplet[]|null Search by feature values
	 * @param $depth integer|null Max depth in returned tree
	 * @param $parent SearchTriplet[]|null Search by feature values in parent items
	 * @param $sorts string[]|null key (feature name) => order (+ or -)
	 * @param $token string|null token. Must match for every root item, so it only makes sense for "$code and nothing else" searches
	 * @param $locations string[]|null Item codes, only their descendants will be searched.
	 *
	 * @return Item[]
	 * @TODO actually implement $parent and $location
	 */
	public function getItem($codes = null, $searches = null, $depth = null, $parent = null, $sorts = null, $token = null, $locations = null) {
		if(self::isArrayAndFull($searches)) {
			$searchSubquery = $this->searchPrepare($searches);
		} else {
			$searchSubquery = '';
		}

		// sanitization
		if(self::isArrayAndFull($codes)) {
			$codes = array_values($codes);
		}

		// Search items by features, filter by location and token, tree lookup using found items as roots
		// (find all descendants) and join with Item, filter by depth, SELECT.
		// The MAX(IF()) bit doesn't make any sense, but it works. It should just be an IF, and at the end of
		// the query there should be "GROUP BY ... MAX(Parent)" according to everyone on the internet, but that
		// didn't work for unfathomable reasons.
		$megaquery = '
        SELECT DescendantItem.`ItemID`, DescendantItem.`Code`, Tree.`Depth`,
        MAX(IF(Parents.`Depth`=1, Parents.`AncestorID`, NULL)) AS Parent
        FROM Tree
        JOIN Item AS AncestorItem ON Tree.AncestorID = AncestorItem.ItemID
        JOIN Item AS DescendantItem ON Tree.DescendantID = DescendantItem.ItemID
        JOIN Tree AS Parents ON DescendantItem.ItemID = Parents.DescendantID
        WHERE AncestorItem.isDefault = 0
        AND Tree.`Depth` <= :depth
        AND Tree.AncestorID IN (
            SELECT `ItemID`
            FROM Item
            ' . $this->implodeOptionalWhereAnd($this->codePrepare($codes), $this->tokenPrepare($token),
				$searchSubquery) . '
        )
        GROUP BY DescendantItem.`ItemID`, DescendantItem.`Code`, Tree.`Depth`
        ORDER BY IFNULL(Tree.`Depth`, 0) ASC
		'; // IFNULL is useless but the intent should be clearer.
		$s = $this->getPDO()->prepare($megaquery);
		// TODO: add a LIMIT clause for pagination

		$s->bindValue(':depth', $this->depthSanitize($depth), \PDO::PARAM_INT);

		if($token != null) {
			$s->bindValue(':token', $token, \PDO::PARAM_STR);
		}

		if(self::isArrayAndFull($codes)) {
			foreach($codes as $numericKey => $code) {
				$s->bindValue(':code' . $numericKey, $code);
			}
		}

		if(self::isArrayAndFull($searches)) {
			foreach($searches as $numericKey => $triplet) {
				/** @var SearchTriplet $triplet */
				$key = $triplet->getKey();
				$value = $triplet->getValue();
				$s->bindValue(':searchname' . $numericKey, $key);
				$s->bindValue(':searchnamedefault' . $numericKey, $key);
				$s->bindValue(':searchvalue' . $numericKey, $value);
				$s->bindValue(':searchvaluedefault' . $numericKey, $value);
			}
		}

		$s->execute();
		if($s->rowCount() === 0) {
			$s->closeCursor();

			return [];
		} else {
			/** @var Item[] map from item ID to Item object (all items) */
			$items = [];
			/** @var Item[] map from item ID to Item objects that require a location being set */
			$needLocation = [];
			/** @var Item[] plain array of results (return this one) */
			$results = [];
			while(($row = $s->fetch(\PDO::FETCH_ASSOC)) !== false) {
				if(isset($items[$row['ItemID']])) {
					$thisItem = $items[$row['ItemID']];
				} else {
					$thisItem = new Item($row['Code']);
					$items[$row['ItemID']] = $thisItem;
				}
				if($row['Depth'] === 0) {
					$results[] = $thisItem;
					if(isset($row['Parent']) && $row['Parent'] !== null) {
						$needLocation[$row['ItemID']] = $thisItem;
					}
				} else {
					if(isset($items[$row['Parent']])) {
						$items[$row['Parent']]->addContent($thisItem);
					} else {
						throw new \LogicException('Cannot find parent ' . $row['Parent'] . ' for Item ' . $thisItem->getCode() . ' (' . $row['ItemID'] . ')');
					}
				}
			}
			$s->closeCursor();
			// object are always passed by reference: update an Item in any array, every other gets updated too
			$this->database->featureDAO()->setFeatures($items);
			$this->database->itemDAO()->setLocations($needLocation);
			$this->sortItems($results, $sorts);

			return $results;
		}
	}

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
	 * @throws InvalidParameterException
	 * @see addItems
	 */
	private function addItem(Item $item, ItemIncomplete $parent = null) {
		if(!($item instanceof Item)) {
			throw new \InvalidArgumentException('Items must be objects of Item class, ' . gettype($item) . ' given'); // will say "object" if it's another object which is kinda useless, whatever
		}

		$isDefault = $item instanceof ItemDefault ? true : false;

		$pdo = $this->getPDO();
		if(!$pdo->inTransaction()) {
			throw new \LogicException('addItem called outside of transaction');
		}

		if($this->addItemStatement === null) {
			$this->addItemStatement = $pdo->prepare('INSERT INTO Item (`Code`, IsDefault, `Default`) VALUES (:c, :isd, :def)');
		}

		$this->addItemStatement->bindValue(':c', $item->getCode(), \PDO::PARAM_STR);
		$this->addItemStatement->bindValue(':isd', $isDefault, \PDO::PARAM_INT);
		if($isDefault || ($default = $item->getDefaultCode()) === null) {
			$this->addItemStatement->bindValue(':def', null, \PDO::PARAM_NULL);
		} else {
			$this->addItemStatement->bindValue(':def', $default, \PDO::PARAM_STR);
		}
		$this->addItemStatement->execute();

		/** @var Item $item */
		$this->database->featureDAO()->addFeatures($item);
		$this->database->modificationDAO()->setItemModified($item);
		$this->database->treeDAO()->addToTree($item, $parent);

		$childItems = $item->getContent();
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

	/**
	 * Exactly what it says on the tin.
	 *
	 * @param Item[] $items Items to be sorted
	 * @param string[] $sortBy key (feature name) => order (+ or -), as provided by Query\Field\Search
	 */
	private function sortItems(&$items, $sortBy = null) {
		if(count($items) <= 1) {
			return;
		}

		// Don't:
		//if(empty($sortBy)) {
		//	return;
		//}
		// items are always sorted by code. Doing it in PHP instead of SQL is probably faster, since only root items are sorted

		usort($items, function($a, $b) use ($sortBy) {
			if(!($a instanceof Item) || !($b instanceof Item)) {
				throw new \InvalidArgumentException('Items must be Item objects');
			}
			if(!empty($sortBy)) {
				$featuresA = $a->getFeatures();
				$featuresB = $b->getFeatures();
				foreach($sortBy as $feature => $order) {
					if(isset($featuresA[$feature]) && isset($featuresB[$feature])) {
						if($order === '+') {
							$result = strnatcmp($featuresA[$feature], $featuresB[$feature]);
						} else {
							$result = strnatcmp($featuresB[$feature], $featuresA[$feature]);
						}
						if($result !== 0) {
							return $result;
						}
					}
				}
			}

			return strnatcmp($a->getCode(), $b->getCode());
		});
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
			$this->database->modificationDAO()->setItemModified($item);
		}
	}

	private $setItemDefaultsStatementSelect = null;
	private $setItemDefaultsStatementUpdate = null;

	private function setItemDefaults(ItemUpdate $item) {
		$db = $this->getPDO();

		if(!$item->getDefaultCodeChanged() || !$item->getIsDefaultChanged()) {
			if($this->setItemDefaultsStatementSelect === null) {
				$this->setItemDefaultsStatementSelect = $db->prepare('SELECT `Default`, IsDefault FROM Item WHERE ItemID = ? LIMIT 1');
			}

			$this->setItemDefaultsStatementSelect->bindValue(1, $this->getItemId($item), \PDO::PARAM_STR);
			$this->setItemDefaultsStatementSelect->execute();
			$result = $this->setItemDefaultsStatementSelect->fetch(\PDO::FETCH_ASSOC);
			$this->setItemDefaultsStatementSelect->closeCursor();

			$isDefault = $item->getIsDefaultChanged() ? (int) $item->getIsDefault() : (int) $result['IsDefault'];
			$defaultId = $item->getDefaultCodeChanged() ? $this->getItemId(new ItemIncomplete($item->getDefaultCode())) : (int) $result['Default'];
		} else {
			$isDefault = (int) $item->getIsDefault();
			$defaultId = $this->getItemId(new ItemIncomplete($item->getDefaultCode()));
		}

		if($this->setItemDefaultsStatementUpdate === null) {
			$this->setItemDefaultsStatementUpdate = $db->prepare('UPDATE Item SET IsDefault = ? AND `Default` = ? WHERE ItemID = ? LIMIT 1');
		}
		$this->setItemDefaultsStatementUpdate->bindValue(1, $isDefault, \PDO::PARAM_INT);
		$this->setItemDefaultsStatementUpdate->bindValue(2, $defaultId, \PDO::PARAM_INT);
		$this->setItemDefaultsStatementUpdate->bindValue(3, $this->getItemId($item), \PDO::PARAM_INT);
		$this->setItemDefaultsStatementUpdate->execute();
	}

	/**
	 * Add location array to items
	 *
	 * @param Item[] $items - array that maps item IDs to Item objects (tree roots only)
	 *
	 * @throws InvalidParameterException - I don't even know anymore
	 */
	private function setLocations($items) {
		if(empty($items)) {
			return;
		}

		$inItemID = DAO::multipleIn(':loc', $items);
		$getLocationsStatement = $this->getPDO()->prepare('SELECT Tree.DescendantID AS ItemID, Item.Code AS Ancestor, Tree.Depth AS Depth
			FROM Item, Tree
			WHERE Tree.AncestorID = Item.ItemID AND Tree.DescendantID IN (' . $inItemID . ') AND Tree.Depth <> 0;
		');

		foreach($items as $itemID => $item) {
			$getLocationsStatement->bindValue(':loc' . $itemID, $itemID, \PDO::PARAM_INT);
		}
		$getLocationsStatement->execute();

		try {
			if($getLocationsStatement->rowCount() > 0) {
				foreach($getLocationsStatement as $row) {
					/** @var Item[] $items */
					$items[$row['ItemID']]->addAncestor((int) $row['Depth'], $row['Ancestor']);
				}
			}
		} finally {
			$getLocationsStatement->closeCursor();
		}
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
	 *
	 * @throws \Exception if Codes table cannot be locked.
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
	 *
	 * @throws \Exception if transaction cannot be committed
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
	 * @throws \Exception if codes cannot be generated (unlockable Codes table, cannot commit, etc...)
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
			$integer ++;
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
