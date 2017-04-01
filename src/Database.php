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

    public function getItem($locations, $searches, $depth, $sortsAscending, $sortsDescending, $token) {
        $items = $this->getItemItself($locations, $searches, $depth, $sortsAscending, $sortsDescending, $token);
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
        SELECT `ItemID`, `ParentID`, `Depth`, `Notes` -- and whatever else is needed (TODO: Code)
        FROM Tree, Item
        WHERE Tree.ItemID = Item.ItemID
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
            SELECT FeatureID, ItemID, FeatureName AS Name, `Value`, ValueText
            FROM Feature, ItemFeature
            WHERE ItemFeature.FeatureID = Feature.FeatureID
            AND ItemID ' . $inItemID . ';
		');
        $s->execute();
        if($s->rowCount() === 0) {
            // TODO: do stuff with $items
        }
    }
}