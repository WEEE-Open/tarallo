<?php

namespace WEEEOpen\Tarallo\Database;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemIncomplete;
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
                    SELECT ItemID
		            FROM Feature, ItemFeature
		            LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
		            WHERE ItemFeature.FeatureID = Feature.FeatureID AND (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
		            AND (' . $this->searchPrepare($searches) . ')
		        )
	        ';
        } else {
	        $searchSubquery = '';
        }

        // Search items by features, filter by location and token, tree lookup using these items as descendants
        // (for /Parent), tree lookup using new root items as roots (find all descendants), filter by depth,
        // join with items, SELECT.
	    $megaquery = '
        SELECT `ItemID`, `Code`, Tree.`AncestorID` AS `AncestorID`, Tree.`Depth` AS `Depth`, directParents.AncestorID AS DirectParent
        FROM Tree, Item
        LEFT JOIN (SELECT * FROM Tree WHERE `Depth` = 1) directParents ON directParents.`DescendantID` = `ItemID`
        WHERE Tree.DescendantID = Item.ItemID
        AND Tree.AncestorID IN (
            SELECT `AncestorID`
            FROM Tree
            WHERE DescendantID IN ( 
                SELECT `ItemID`
                FROM Item
                ' . $this->implodeOptionalWhereAnd($this->locationPrepare($locations), $this->tokenPrepare($token), $searchSubquery) . '
            ) AND `Depth` = :parent
        ) AND Tree.`Depth` <= :depth AND isDefault = 0
        ORDER BY IFNULL(directParents.`Depth`, 0) ASC;
		';
        $s = $this->getPDO()->prepare($megaquery);


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
        	$items = [];
        	$itemsTreeRoots = []; // array of root Item
			while (($row = $s->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$thisItem = new Item($row['Code']);
				$items[$row['ItemID']] = $thisItem;
				if($row['DirectParent'] === null) {
					$itemsTreeRoots[] = $thisItem;
				} else {
					if(isset($items[$row['DirectParent']])) {
						// this also updates $itemsTreeRoots since objects are always passed by reference
						/** @var Item[] $items */
						$items[$row['DirectParent']]->addChild($thisItem);
					} else {
						throw new \LogicException('Missing parent for item ' . (string) $thisItem . ', possibly incosistent Tree table state');
					}
				}
			}
	        $s->closeCursor();
			$this->database->featureDAO()->setFeatures($items);
			$this->sortItems($itemsTreeRoots, $sorts);
            return $itemsTreeRoots;
        }
    }

    public function addItems($items, ItemIncomplete $parent = null, $default = false) { // TODO: somehow find parent (pass code from JSON request?)
        if($items instanceof Item) {
            $items = [$items];
        } else if(!is_array($items)) {
            throw new \InvalidArgumentException('Items must be passed as an array or a single Item');
        }

        if(empty($items)) {
            return;
        }

        if($parent instanceof ItemIncomplete) {
        	$parent = $this->getItemId($parent);
        }

        foreach($items as $item) {
            $this->addItem($item, $parent, $default);
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
	 * @param bool $default
	 *
	 * @see addItems
	 *
	 */
    private function addItem(Item $item, ItemIncomplete $parent = null, $default = false) {
        if(!($item instanceof Item)) {
            throw new \InvalidArgumentException('Items must be objects of Item class, ' . gettype($item) . ' given'); // will say "object" if it's another object which is kinda useless, whatever
        }

        $pdo = $this->getPDO();
        if(!$pdo->inTransaction()) {
            throw new \LogicException('addItem called outside of transaction');
        }

        if($this->addItemStatement === null) {
	        $this->addItemStatement = $pdo->prepare('INSERT INTO Item (`Code`, IsDefault) VALUES (:c, :d)');
        }

	    $this->addItemStatement->bindValue(':c', $item->getCode(), \PDO::PARAM_STR);
	    $this->addItemStatement->bindValue(':d', $default, \PDO::PARAM_INT);
	    $this->addItemStatement->execute();

	    /** @var Item $item */
	    $this->database->featureDAO()->addFeatures($item);

	    $this->setItemModified($item);

	    $this->addToTree($item, $parent);

	    $childItems = $item->getChildren();
	    foreach($childItems as $childItem) {
	    	// yay recursion!
	    	$this->addItem($childItem, $item, $default);
	    }
    }

    private $itemModifiedStatement = null;

    private function setItemModified(ItemIncomplete $item) {
        $pdo = $this->getPDO();
        if($this->itemModifiedStatement === null) {
	        $this->itemModifiedStatement = $pdo->prepare('INSERT INTO ItemModification (ModificationID, ItemID) SELECT ?, ItemID FROM Item WHERE Item.Code = ?');
        }
	    $this->itemModifiedStatement->execute([$this->database->getModificationId(), $this->getItemId($item)]);
    }

	private function addToTree(ItemIncomplete $child, ItemIncomplete $parent = null) {
    	if($parent === null) {
    		$parent = $child;
	    }

    	$this->setParentInTree($parent, $child);
    }

	private $setParentInTreeStatement = null;
	/**
	 * addEdge, basically. It's better to use addToTree(), which in turn calls this function.
	 *
	 * @see addToTree
	 * @param ItemIncomplete $parent
	 * @param ItemIncomplete $child
	 */
	private function setParentInTree(ItemIncomplete $parent, ItemIncomplete $child) {
    	$pdo = $this->getPDO();
    	if($this->setParentInTreeStatement === null) {
		    $this->setParentInTreeStatement = $pdo->prepare('
			INSERT INTO Tree(AncestorID, DescendantID, Depth)
			SELECT AncestorID, :new1, Depth+1
			FROM Tree
			WHERE DescendantID = :parent
			UNION
			SELECT :new2, :new3, 0;');
	    }
		$this->setParentInTreeStatement->bindValue(':parent', $this->getItemId($parent), \PDO::PARAM_INT);
		$this->setParentInTreeStatement->bindValue(':new1', $this->getItemId($child), \PDO::PARAM_INT);
		$this->setParentInTreeStatement->bindValue(':new2', $this->getItemId($child), \PDO::PARAM_INT);
		$this->setParentInTreeStatement->bindValue(':new3', $this->getItemId($child), \PDO::PARAM_INT);
		$this->setParentInTreeStatement->execute();
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
	 * @param Item[] $items
	 * @param string[] $sorts
	 */
	private function sortItems(&$items, $sorts = null) {
		if(count($items) < 2) {
			return;
		}
 		$sortBy = [];
		if($sorts !== null) {
			foreach($sorts as $string) {
				if(!is_string($sorts)) {
					throw new \InvalidArgumentException('Sort conditions must be strings, ' . gettype($sorts) . ' given');
				}
				if(strlen($string) > 1) {
					if($string[0] === '+' || $string[0] === '-') {
						$sortBy[ substr($string, 1) ] = $string[0];
					}
				}
			}
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
}