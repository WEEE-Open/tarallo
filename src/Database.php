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

	private function locationPrepare($locations) {
        if(self::isArrayAndFull($locations)) {
            $locationWhere = 'AND `Name` IN (';
            foreach($locations as $k => $loc) {
                $locationWhere .= ':location'.$k.', ';
            }
            return rtrim($locationWhere, ', ').')';
        } else {
            return '';
        }
    }


    private function searchPrepare($searches) {
		// TODO: this need more thought, searches are for Feature(s)
        if(self::isArrayAndFull($searches)) {
            $where = 'AND (';
            foreach($searches as $k => $loc) {
                $where .= '`Name` LIKE :search'.$k.' OR '; // TODO: %
            }
            return substr($where, 0, strlen($where)-4).')'; // remove last " OR "
        } else {
            return '';
        }
    }

    private function sortPrepare($sortsAscending, $sortsDescending) {
        if(self::isArrayAndFull($sortsAscending) || self::isArrayAndFull($sortsDescending)) {
            $order = 'ORDER BY ';
            if(self::isArrayAndFull($sortsAscending)) {
                foreach($sortsAscending as $key) {
                    $order .= $key . ' ASC, '; // TODO: comma?
                }
            }
            if(self::isArrayAndFull($sortsAscending)) {
                foreach($sortsAscending as $key) {
                    $order .= $key . ' DESC, ';
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

    private static function implodeOptionalWhereAnd() {
        $args = func_get_args();
        $where = self::implodeAnd($args);
        if($where === '') {
            return '';
        } else {
            return 'WHERE ' . $where;
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
        // TODO: get features
    }

	public function getItemItself($locations, $searches, $depth, $sortsAscending, $sortsDescending, $token) {
		$sortOrder  = $this->sortPrepare($sortsAscending, $sortsDescending); // $arrayOfSortKeysAndOrder wasn't a very good name, either...
		$innerWhere = $this->implodeOptionalWhereAnd($this->depthPrepare($depth), $this->locationPrepare($locations), $this->searchPrepare($searches));
		$outerWhere = $this->implodeOptionalAnd($this->tokenPrepare($token));

		// TODO: this will probably blow up in a spectacular way.
		/** @noinspection SqlResolve */
		$s = $this->getPDO()->prepare('
        SELECT `Name`, `Type`, `Status`, `Owner`, `SuppliedBy`, `Borrowed`, `Notes`
        FROM Item, Tree
        WHERE AncestorID IN (
            SELECT ItemID
            FROM Item
            ' . $innerWhere . '
        )
        ' . $outerWhere . $sortOrder . ';

		');
		$s->execute();
		if($s->rowCount() === 0) {
			return [];
		} else {
			return $s->fetchAll();
		}
	}
}