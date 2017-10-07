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

	private function parentSanitize($parent) {
    	if(is_numeric($parent)) {
    		return (int) $parent;
	    } else {
			return 0;
		}
	}

	/**
	 * Prepare location part of query. Due to the use of multipleIn, sanitize array keys before passing them in
	 * (e.g. use array_values).
	 *
	 * @param array $locations
	 * @return string
	 * @todo: shouldn't this search items INSIDE a location, instead of the location itself?
	 */
    private function locationPrepare($locations) {
        if(self::isArrayAndFull($locations)) {
            $locationWhere = '`Code` IN (' . $this->multipleIn(':location', $locations);
            return $locationWhere . ')';
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
	 * @param $searches array of SearchTriplet
	 * @return string piece of query string
	 * @see FeatureDAO::getWhereStringFromSearches
	 */
	private function searchPrepare($searches) {
		if(!is_array($searches)) {
			throw new \InvalidArgumentException('Search parameters must be passed as an array');
		}
		if(empty($searches)) {
			return '';
		}

		return $this->database->featureDAO()->getWhereStringFromSearches($searches);
    }

    private static function implodeOptionalWhereAnd() {
        $args = func_get_args();
        $where = self::implodeAnd($args);
        if($where === '') {
            return '';
        } else {
            return ' WHERE ' . $where;
        }
    }

    /**
     * Join non-empty string arguments via " AND " to add in a WHERE clause.
     *
     * @see implodeOptionalWhereAnd
     * @param $args string[]
     * @return string empty string or WHERE clauses separated by AND (no WHERE itself)
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

    public function getItem($locations, $searches, $depth, $parent, $sorts, $token) {
        if(self::isArrayAndFull($searches)) {
	        $searchSubquery = '
	            ItemID IN (
                    SELECT Item.ItemID
		            FROM Item, Feature, ItemFeature
		            LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
		            WHERE (Item.ItemID = ItemFeature.ItemID OR Item.`Default` = ItemFeature.ItemID) AND ItemFeature.FeatureID = Feature.FeatureID AND (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL) AND IsDefault = 0
		            AND (' . $this->searchPrepare($searches) . ')
	        )';
        } else {
	        $searchSubquery = '';
        }

        // sanitization
        if(self::isArrayAndFull($locations)) {
        	$locations = array_values($locations);
        }

        // Search items by features, filter by location and token, tree lookup using these items as descendants
        // (for /Parent), tree lookup using new root items as roots (find all descendants) and join with Item,
	    // filter by depth, SELECT.
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
            SELECT `AncestorID`
            FROM Tree
            WHERE `Depth` = :parent
            AND DescendantID IN ( 
                SELECT `ItemID`
                FROM Item
                ' . $this->implodeOptionalWhereAnd($this->locationPrepare($locations), $this->tokenPrepare($token), $searchSubquery) . '
            )
        )
        GROUP BY DescendantItem.`ItemID`, DescendantItem.`Code`, Tree.`Depth`
        ORDER BY IFNULL(Tree.`Depth`, 0) ASC
		'; // IFNULL is useless but the intent should be clearer.
        $s = $this->getPDO()->prepare($megaquery);
        // TODO: add a LIMIT clause for pagination


        $s->bindValue(':parent', $this->parentSanitize($parent), \PDO::PARAM_INT);
        $s->bindValue(':depth', $this->depthSanitize($depth), \PDO::PARAM_INT);

        if($token != null) {
	        $s->bindValue(':token', $token, \PDO::PARAM_STR);
        }

        if(self::isArrayAndFull($locations)) {
	        foreach($locations as $numericKey => $location) {
		        $s->bindValue(':location' . $numericKey, $location);
	        }
        }

	    if(self::isArrayAndFull($searches)) {
		    foreach($searches as $numericKey => $triplet) {
			    /** @var SearchTriplet $triplet */
			    $s->bindValue(':searchname' . $numericKey, $triplet->getKey());
			    $s->bindValue(':searchvalue' . $numericKey, $triplet->getValue());
		    }
	    }

        $s->execute();
        if($s->rowCount() === 0) {
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
			throw new InvalidParameterException('Unknown item ' . $item->getCode());
		} else {
			$id = (int) $this->getItemIdStatement->fetch(\PDO::FETCH_NUM)[0];
			$this->getItemIdStatement->closeCursor();
			$this->getItemIdCache[$code] = $id;
			return $id;
		}
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

		if(empty($sortBy)) {
			return;
		}

		usort($items, function ($a, $b) use ($sortBy) {
			if(!($a instanceof Item) || !($b instanceof Item)) {
				throw new \InvalidArgumentException('Items must be Item objects');
			}
			if(!empty($sortBy)) {
				$featuresA = $a->getFeatures();
				$featuresB = $b->getFeatures();
				foreach($sortBy as $feature => $order) {
					if(isset($featuresA[$feature]) && isset($featuresB[$feature])) {
						if($order === '+') {
							$result = strnatcmp($featuresA[ $feature ], $featuresB[ $feature ]);
						} else {
							$result = strnatcmp($featuresB[ $feature ], $featuresA[ $feature ]);
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
	 * @throws InvalidParameterException
	 */
	private function setLocations($items) {
		$inItemID = DAO::multipleIn(':loc', $items);
		$getLocationsStatement = $this->getPDO()->prepare('SELECT Tree.DescendantID AS ItemID, Item.Code AS Ancestor, Tree.Depth AS Depth
			FROM Item, Tree
			WHERE Tree.AncestorID = Item.ItemID AND Tree.DescendantID IN (' . $inItemID . ') AND Tree.Depth <> 0;
		');

		foreach($items as $itemID => $item) {
			$getLocationsStatement->bindValue(':loc' . $itemID, $itemID, \PDO::PARAM_INT);
		}
		$getLocationsStatement->execute();

		if($getLocationsStatement->rowCount() > 0) {
			foreach($getLocationsStatement as $row) {
				/** @var Item[] $items */
				$items[$row['ItemID']]->addAncestor((int) $row['Depth'], $row['Ancestor']);
			}
			$getLocationsStatement->closeCursor();
		}
	}

	private $getNextCodeStatement = null;
	private $setNextCodeStatement = null;
	private $lockTablesStatement  = null;

	/**
	 * Get next available code with specified prefix
	 * @param string $prefix
	 *
	 * @return string next available code, prefix included
	 * @throws \Exception if Codes table cannot be locked. Locking it avoids generating the same code twice in different transactions. Codes table is used only when creating new items, so its performance isn't really that critical. Lock should be released on commit/rollback.
	 */
	private function getNextCode($prefix = '') {
		if($this->lockTablesStatement === null) {
			$this->lockTablesStatement = $this->getPDO()->prepare("LOCK TABLE Codes WRITE");
		}
		$locked = $this->lockTablesStatement->execute();
		if(!$locked) {
			throw new \Exception('Cannot generate code (cannot lock Codes table)');
		}

		if($this->getNextCodeStatement === null) {
			$this->getNextCodeStatement = $this->getPDO()->prepare('SELECT `Integer` FROM Codes WHERE Prefix = ?');
		}
		$this->getNextCodeStatement->bindValue(1, $prefix, \PDO::PARAM_STR);
		$this->getNextCodeStatement->execute();
		if($this->getNextCodeStatement->rowCount() > 0) {
			$result = $this->getNextCodeStatement->fetchAll(\PDO::FETCH_ASSOC);
			$integer = (int) $result['Integer'];
			$this->getNextCodeStatement->closeCursor();
		} else {
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
		$result = $this->checkIfItemExistsStatement->fetchAll(\PDO::FETCH_ASSOC);
		$this->checkIfItemExistsStatement->closeCursor();
		return ((int) $result['c']) > 0;
	}
}
