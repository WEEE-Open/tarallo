<?php

namespace WEEEOpen\Tarallo;

class Database {
	/** @var \PDO */
	private $pdo = null;

	private function getPDO() {
		if($this->pdo === null) {
			$this->connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
		}
		return $this->pdo;
	}

	private function connect($user, $pass, $dsn) {
		try {
			$this->pdo = new \PDO($dsn, $user, $pass, [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_AUTOCOMMIT => false,
				\PDO::ATTR_EMULATE_PREPARES => false,
			]);
		} catch (\PDOException $e) {
			throw new \Exception('Cannot connect to database: ' . $e->getMessage());
		}
	}

	public function disconnect() {
		$this->pdo = null;
	}

	public function getUserFromSession($session) {
		$s = $this->getPDO()->prepare('SELECT Name, Password FROM `User` WHERE `Session` = ? AND `SessionExpiry` > ? AND `Enabled` > 0');
		$s->execute([$session, time()]);
		if($s->rowCount() > 1) {
			throw new \LogicException('Duplicate session session identifier in database');
		} else if($s->rowCount() === 0) {
			return null;
		} else {
			$user = $s->fetch();
			return new User($user['Name'], null, $user['password']);
		}
	}

	public function setSessionFromUser($username, $session, $expiry) {
		$pdo = $this->getPDO();
		$pdo->beginTransaction();
		$s = $this->getPDO()->prepare('UPDATE `User` SET `Session` = :s, SessionExpiry = :se WHERE `Name` = :n AND `Enabled` > 0');
		$s->bindValue(':s', $session);
		$s->bindValue(':se', $expiry);
		$s->bindValue(':n', $username);
		$s->execute();
		$pdo->commit();
	}

	/**
	 * Log in a user, via username and password. Doesn't start any session!
	 *
	 * @param $username string username
	 * @param $password string plaintext password
	 *
	 * @return null|User User if found and password is valid, null otherwise
	 */
	public function getUserFromLogin($username, $password) {
		$s = $this->getPDO()->prepare('SELECT Password FROM `User` WHERE `Name` = ? AND `Enabled` > 0');
		$s->execute([$username]);
		if($s->rowCount() > 1) {
			throw new \LogicException('Duplicate username in database (should never happen altough MySQL doesn\'t allow TEXT fields to be UNIQUE, since that would be too easy and suitable for the current millennium)');
		} else if($s->rowCount() === 0) {
			return null;
		} else {
			$user = $s->fetch();
			try {
				return new User($username, $password, $user['Password']);
			} catch(\InvalidArgumentException $e) {
				if($e->getCode() === 72) {
					return null;
				} else {
					throw $e;
				}
			}
		}
	}

	private function depthPrepare($depth) {
        if(is_int($depth)) {
            return 'WHERE `Depth` <= :depth';
        } else {
            return 'WHERE `Depth` IS NOT NULL';
        }
    }

    private function multipleIn($prefix, $array) {
        $in = 'IN (';
        foreach($array as $k => $v) {
            $in .= $prefix . $k . ', ';
        }
        return substr($in, 0, strlen($in) - 2) . ')'; //remove last ', '
    }

	private function locationPrepare($locations) {
        if(self::isArrayAndFull($locations)) {
            $locationWhere = 'AND `Name` ' . $this->multipleIn(':location', $locations);
            return rtrim($locationWhere, ', ').')';
        } else {
            return '';
        }
    }


    private function searchPrepare($searches) {
		// TODO: this need more thought, searches are for Feature(s)
        // TODO: search numeric values too!
        if(self::isArrayAndFull($searches)) {
            $where = '(';
            foreach($searches as $k => $loc) {
                $where .= '(`FeatureName` = :searchname'.$k.' AND ValueText LIKE :searchkey'.$k.' OR '; // TODO: %
            }
            return substr($where, 0, strlen($where)-4).')'; // remove last " OR "
        } else {
            return '';
        }
    }

    private function sortPrepare($sorts) {
        if(self::isArrayAndFull($sorts)) {
            $order = 'ORDER BY ';
            if(self::isArrayAndFull($sorts)) {
                foreach($sorts as $key => $ascdesc) {
                    $order .= $key . ' ' . $ascdesc . ', ';
                }
            }
            return $order;
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

    private static function isArrayAndFull($something) {
        if(is_array($something) && !empty($something)) {
            return true;
        } else {
            return false;
        }
    }

    private static function implodeOptionalAndAnd() {
        $args = func_get_args();
        $where = self::implodeAnd($args);
        if($where === '') {
            return '';
        } else {
            return ' AND ' . $where;
        }
    }

    private static function implodeOptionalAnd() {
        $args = func_get_args();
        return self::implodeAnd($args);
    }

    /**
     * Join non-empty string arguments via " AND " to add in a WHERE clause.
     *
     * @see implodeOptionalAnd
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

    public function getItem($locations, $searches, $depth, $sorts, $token) {
        $items = $this->getItemItself($locations, $searches, $depth, $sorts, $token);
        $itemIDs = []; // TODO: implement
        if(!empty($itemIDs)) {
            $this->getFeatures($itemIDs, $items);
        }
    }

	private function getItemItself($locations, $searches, $depth, $sorts, $token) {
		$sortOrder  = $this->sortPrepare($sorts); // $arrayOfSortKeysAndOrder wasn't a very good name, either...
		$whereLocationToken = $this->implodeOptionalAnd($this->locationPrepare($locations), $this->tokenPrepare($token));
		$searchWhere = $this->implodeOptionalAndAnd($this->searchPrepare($searches));
        $parentWhere = $this->implodeOptionalAnd(''); // TODO: implement, "WHERE Depth = 0" by default, use = to find only the needed roots (descendants are selected via /Depth)
        $depthDefaultWhere  = $this->implodeOptionalAnd($this->depthPrepare($depth), 'isDefault = 0');

		// This will probably blow up in a spectacular way.
        // Search items by features, filter by location and token, tree lookup using these items as descendants
        // (for /Parent), tree lookup using new root items as roots (find all descendants), filter by depth,
        // join with items, SELECT.
        // TODO: somehow sort the result set (not the innermost query, Parent returns other items...).
		$s = $this->getPDO()->prepare('
        SELECT `ItemID`, `Code`, `AncestorID`, `Depth`
        FROM Tree, Item
        WHERE Tree.AncestorID = Item.ItemID
        AND AncestorID IN (
            SELECT `ItemID`
            FROM Tree
            WHERE DescendantID IN ( 
                SELECT `ItemID`
                FROM Item
                WHERE
                ' . $whereLocationToken . '
                AND ItemID IN (
                    SELECT `ItemID`
                    FROM ItemFeature, Feature
                    WHERE Feature.FeatureID = ItemFeature.FeatureID
                    ' . $searchWhere . '
                )
            ) AND ' . $parentWhere . ';
        ) ' . $depthDefaultWhere . '
        

		');
		$s->execute();
		if($s->rowCount() === 0) {
			return [];
		} else {
			return $s->fetchAll(); // TODO: return Item objects
		}
	}

	private function getFeatures($itemIDs, &$items) {
        $inItemID = $this->multipleIn(':item', $itemIDs);
        $s = $this->getPDO()->prepare('
            SELECT ItemID, FeatureName, `Value`, ValueText
            FROM Feature, ItemFeature
            WHERE ItemFeature.FeatureID = Feature.FeatureID
            AND ItemID ' . $inItemID . '
		');
        $s->execute();
        if($s->rowCount() === 0) {
            // TODO: do stuff with $items
        }
    }

    public function addItems($items, $default = false) {
    	if($items instanceof Item) {
    		$items = [$items];
	    } else if(!is_array($items)) {
		    throw new \InvalidArgumentException('Items must be passed as an array or a single Item');
	    }

	    if(empty($items)) {
    		return;
	    }

	    $pdo = $this->getPDO();

	    $itemQuery = $pdo->prepare('INSERT INTO Item (`Code`, IsDefault) VALUES (:c, :d)');
	    $itemQuery->bindValue(':d', $default, \PDO::PARAM_INT);
	    // not very nice, but the alternative was another query in a separate function (even slower) or returning FeatureID from getFeatureTypeFromName, which didn't make any sense, or returning a Feature object which I may do in future and increases complexity for almost no benefit
	    $featureNumber = $pdo->prepare('INSERT INTO ItemFeature (FeatureID, ItemID, `Value`)     SELECT FeatureName, :item, :val FROM Feature WHERE Feature.FeatureName = :feature');
	    $featureText   = $pdo->prepare('INSERT INTO ItemFeature (FeatureID, ItemID, `ValueText`) SELECT FeatureName, :item, :val FROM Feature WHERE Feature.FeatureName = :feature');
	    $featureEnum   = $pdo->prepare('INSERT INTO ItemFeature (FeatureID, ItemID, `ValueEnum`) SELECT FeatureName, :item, :val FROM Feature WHERE Feature.FeatureName = :feature');
	    foreach($items as $item) {
			$id = $this->addItem($itemQuery, $item);
			/** @var Item $item */
			$featureNumber->bindValue(':item', $id);
			$featureText->bindValue(':item', $id);
			$featureEnum->bindValue(':item', $id);
			$features = $item->getFeatures();
			foreach($features as $feature => $value) {
				$featureType = $this->getFeatureTypeFromName($feature);
				switch($featureType) {
					// was really tempted to use variable variables here...
					case self::FEATURE_TEXT:
						$featureText->bindValue(':feature', $feature);
						$featureText->bindValue(':value', $value);
						$featureText->execute();
						break;
					case self::FEATURE_NUMBER:
						$featureText->bindValue(':feature', $feature);
						$featureNumber->bindValue(':value', $value);
						$featureNumber->execute();
						break;
					case self::FEATURE_ENUM:
						$featureText->bindValue(':feature', $feature);
						$featureEnum->bindValue(':value', $this->getFeatureValueEnumFromName($feature, $value));
						$featureEnum->execute();
						break;
					default:
						throw new \LogicException('Unknown feature type ' . $featureType . ' returned by getFeatureTypeFromName (should never happen unless a cosmic ray flips a bit somewhere)');
				}
			}
			$this->setItemModified($id);
	    }

	    return;
    }

	/**
	 * Insert a single item into the database, return its id. Basically just add a row to Item, no features are added.
	 * Must be called while in transaction.
	 *
	 * @param \PDOStatement $itemQuery the query used by addItem, with :d already bound
	 * @param Item $item the item to be inserted
	 * @see addItems
	 *
	 * @return int ItemID. 0 may also mean "error", BECAUSE PDO.
	 */
    private function addItem(\PDOStatement $itemQuery, Item $item) {
	    if(!($item instanceof Item)) {
		    throw new \InvalidArgumentException('Items must be objects of Item class, ' . gettype($item) . ' given'); // will say "object" if it's another object which is kinda useless, whatever
	    }

	    $pdo = $this->getPDO();
	    if(!$pdo->inTransaction()) {
	    	throw new \LogicException('addItem called outside of transaction');
	    }
	    $itemQuery->bindValue(':c', $item->getCode(), \PDO::PARAM_STR);
	    $itemQuery->execute();
	    return (int) $pdo->lastInsertId();
    }

	private $featureTypeStatement = null;
    const FEATURE_TEXT = 0;
    const FEATURE_NUMBER = 1;
    const FEATURE_ENUM = 2;

    public function getFeatureTypeFromName($featureName) {
    	$pdo = $this->getPDO();
		if($this->featureTypeStatement === null) {
			$this->featureTypeStatement = $pdo->prepare('SELECT `FeatureType` FROM FeatureValue, Feature WHERE Feature.FeatureID = FeatureValue.FeatureID AND Feature.FeatureName = ? LIMIT 1');
		}
		$this->featureTypeStatement->bindValue(1, $featureName);
		$this->featureTypeStatement->execute();
	    if($this->featureTypeStatement->rowCount() === 0) {
		    throw new InvalidParameterException('Unknown feature name ' . $featureName);
	    }
	    switch((int) $this->featureTypeStatement->fetch(\PDO::FETCH_NUM)[0]) {
		    case 0:
		    	return self::FEATURE_TEXT;
		    case 1:
		    	return self::FEATURE_NUMBER;
		    case 2:
			    return self::FEATURE_ENUM;
		    default:
			    throw new \LogicException('Unknown feature type for ' . $featureName . ' found in database');
	    }
    }

	private $featureEnumNameStatement = null;

    public function getFeatureValueEnumFromName($featureName, $featureValueText) {
	    $pdo = $this->getPDO();
	    if($this->featureEnumNameStatement === null) {
		    $this->featureEnumNameStatement = $pdo->prepare('SELECT `ValueEnum` FROM FeatureValue, Feature WHERE Feature.FeatureID = FeatureValue.FeatureID AND Feature.FeatureName = :n AND FeatureValue.ValueText = :valuetext AND Feature.FeatureType = :type LIMIT 1');
	    }
	    $this->featureEnumNameStatement->bindValue(':n', $featureName);
	    $this->featureEnumNameStatement->bindValue(':valuetext', $featureValueText);
	    $this->featureEnumNameStatement->bindValue(':type', self::FEATURE_ENUM);
	    $this->featureEnumNameStatement->execute();
	    if($this->featureEnumNameStatement->rowCount() === 0) {
		    throw new InvalidParameterException('Invalid value ' . $featureValueText . ' for feature ' . $featureName);
	    }
	    $result = $this->featureEnumNameStatement->fetch();
	    return $result['ValueEnum'];
	}

	private function getNewModificationId(User $user, $notes) {
    	$pdo = $this->getPDO();
		$stuff = $pdo->prepare('INSERT INTO Modification (UserID, `Date`, Notes) SELECT `User`.UserID, :dat, :notes FROM `User` WHERE `User`.Name = :username');
		$stuff->bindValue(':username', $user->getUsername());
		$stuff->bindValue(':dat', time());
		$stuff->bindValue(':notes', $notes);
		$stuff->execute();
		return $pdo->lastInsertId();
	}

	private $currentModificationId = null;

	public function modifcationBegin(User $user, $notes = null) {
		// TODO: check user?
    	$pdo = $this->getPDO();
    	if($pdo->inTransaction()) {
		    throw new \LogicException('Trying to start nested transactions in modificationBegin');
	    }
	    $pdo->beginTransaction();
    	$this->currentModificationId = $this->getNewModificationId($user, $notes);
	}

	public function modificationCommit() {
		$this->getPDO()->commit();
		$this->currentModificationId = null;
	}

	public function modificationRollback() {
		$this->getPDO()->rollBack();
		$this->currentModificationId = null;
	}

	private function getModificationId() {
		if(!$this->getPDO()->inTransaction()) {
			throw new \LogicException('Trying to read modification ID without an active transaction');
		}
		if($this->currentModificationId === null) {
			throw new \LogicException('Transaction started but no modification ID set (= something went horribly wrong)');
		}
		return $this->currentModificationId;
	}

	private function setItemModified($itemID) {
		$pdo = $this->getPDO();
		$stuff = $pdo->prepare('INSERT INTO ItemModification (ModificationID, ItemID) VALUES (?, ?)');
		$stuff->execute([$this->getModificationId(), $itemID]);
	}
}