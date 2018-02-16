<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\SearchTriplet;

final class SearchDAO extends DAO {
	/**
	 * Prepare "code" part of query.
	 *
	 * @param array $codes
	 *
	 * @return string
	 */
	private function codePrepare($codes) {
		if(self::isArrayAndFull($codes)) {
			$pieces = [];
			foreach($codes as $k => $code) {
				if(!is_integer($k)) {
					throw new \InvalidArgumentException('Codes array keys should be integers, ' . $k . ' given');
				}
				if(strpos($code, '%') === false && strpos($code, '_') === false) {
					$pieces[] = '`Code` = :code' . $k;
				} else {
					$pieces[] = '`Code` LIKE :code' . $k;
				}
			}
			$result = self::implodeOptional($pieces, ' OR ');
			if(strlen($result) <= 0) {
				return '';
			} else {
				return '(' . $result . ')';
			}
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
	 * Bind :searchkey0, :searchdefaultkey0, :searchkey1, ... to keys and :searchvalue0, :searchdefaultvalue0, ... to
	 * values.
	 *
	 * @param SearchTriplet[] $searches array of SearchTriplet
	 *
	 * @return string piece of query string
	 * @see FeatureDAO::getWhereStringFromSearches
	 */
	private function searchPrepare($searches) {
		if(!self::isArrayAndFull($searches)) {
			throw new \InvalidArgumentException('Search parameters must be passed as a non-empty array');
		}

		$subquery = '';
		$wheres = $this->database->featureDAO()->getWhereStringFromSearches($searches, 'search');
		$wheresdefault = $this->database->featureDAO()->getWhereStringFromSearches($searches, 'searchdefault');
		if(count($wheres) <= 0 || count($wheresdefault) <= 0) {
			throw new \LogicException('getWhereStringFromSearches() did not return anything, but there were ' . count($searches) . ' search parameters');
		}

		foreach($wheres as $k => $where) {
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
	 * Get search subquery for parent features.
	 * Works like searchPrepare.
	 *
	 * @param SearchTriplet[] $parentFeatures array of SearchTriplet
	 *
	 * @return string piece of query string
	 * @see SearchDAO::searchPrepare()
	 */
	private function parentPrepare($parentFeatures) {
		if(!self::isArrayAndFull($parentFeatures)) {
			throw new \InvalidArgumentException('Search parameters must be passed as a non-empty array');
		}

		$wheres = $this->database->featureDAO()->getWhereStringFromSearches($parentFeatures, 'parent');
		$wheresdefault = $this->database->featureDAO()->getWhereStringFromSearches($parentFeatures, 'parentdefault');
		if(count($wheres) <= 0 || count($wheresdefault) <= 0) {
			throw new \LogicException('getWhereStringFromSearches() did not return anything, but there were ' . count($parentFeatures) . ' search parameters for parent items');
		}

		$subquery = '';
		foreach($wheres as $k => $where) {
			// Depth > 0 excludes matching item itself, since it should be a parent/ancestor to returned items... It's a lacchezzo abnorme™, basically.
			$subquery .= '
			AND (
				ParentSubqueryTree.AncestorID IN (SELECT Item.ItemID ' . $where . ' AND Depth > 0)
				OR
				ParentSubqueryItem.`Default` IN (SELECT Item.ItemID ' . $wheresdefault[$k] . ' AND Depth > 0)
			)';
		}

		$query = '
			Tree.AncestorID IN (
				SELECT ParentSubqueryTree.DescendantID
				FROM Tree AS ParentSubqueryTree, Item AS ParentSubqueryItem
				WHERE ParentSubqueryTree.AncestorID=ParentSubqueryItem.ItemID
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
		$where = self::implodeOptional($args, ' AND ');
		if($where === '') {
			return '';
		} else {
			return 'WHERE ' . $where;
		}
	}

	/**
	 * Concatenate strings with separator (" AND " or " OR ", usually), ignore empty strings.
	 *
	 * @param $args string[]
	 * @param $glue string
	 *
	 * @return string Concatenated strings or
	 */
	private static function implodeOptional($args, $glue) {
		if(!is_array($args)) {
			throw new \InvalidArgumentException('implodeOptional expected an array, ' . gettype($args) . ' given');
		}
		if(!is_string($glue) || strlen($glue) <= 0) {
			throw new \InvalidArgumentException('implodeOptional "glue" should be a non-empty string');
		}
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

		return implode($glue, $stuff);
	}

	public function getItems(Search $search) {
		if(self::isArrayAndFull($searches)) {
			$searchSubquery = $this->searchPrepare($searches);
		} else {
			$searchSubquery = '';
		}

		if(self::isArrayAndFull($ancestors)) {
			$parentSubquery = $this->parentPrepare($ancestors);
		} else {
			$parentSubquery = '';
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
        JOIN Item AS AncestorItem ON Tree.Ancestor = AncestorItem.ItemID
        JOIN Item AS DescendantItem ON Tree.Descendant = DescendantItem.ItemID
        JOIN Tree AS Parents ON DescendantItem.ItemID = Parents.DescendantID
        WHERE AncestorItem.isDefault = 0
        AND Tree.`Depth` <= :depth
        AND Tree.Ancestor IN (
            SELECT `Code`
            FROM Item
            ' . $this->implodeOptionalWhereAnd($this->codePrepare($codes), $this->tokenPrepare($token),
				$parentSubquery, $searchSubquery) . '
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
				$s->bindValue(':searchdefaultname' . $numericKey, $key);
				$s->bindValue(':searchvalue' . $numericKey, $value);
				$s->bindValue(':searchdefaultvalue' . $numericKey, $value);
			}
		}

		if(self::isArrayAndFull($ancestors)) {
			foreach($ancestors as $numericKey => $triplet) {
				/** @var SearchTriplet $triplet */
				$key = $triplet->getKey();
				$value = $triplet->getValue();
				$s->bindValue(':parentname' . $numericKey, $key);
				$s->bindValue(':parentdefaultname' . $numericKey, $key);
				$s->bindValue(':parentvalue' . $numericKey, $value);
				$s->bindValue(':parentdefaultvalue' . $numericKey, $value);
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
			$this->database->featureDAO()->getFeaturesAll($items);
			$this->setLocations($needLocation);
			$this->sortItems($results, $sorts);
			$totalCount = count($results);
			if($total !== null) {
				$total = $totalCount;
			}
			$this->paginateItems($results, $page, $pageLimit);
			if($pages !== null) {
				$pages = (int) ceil($totalCount / $pageLimit);
			}

			return array_values($results); // Reindex array so json_encode considers it an array and not an object (associative array)
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

	/**
	 * Remove items outside current page
	 *
	 * @param $items Item[] Reference to items, will be changed in-place
	 * @param $page int current page, starting from 1
	 * @param $perPage int items per page, -1 returns everything
	 *
	 * @deprecated use the Order column and LIMIT
	 */
	private function paginateItems(&$items, $page, $perPage = -1) {
		if($perPage === -1) {
			return;
		}
		if($perPage <= 0) {
			throw new \InvalidArgumentException('Items per page should be -1 or a positive number, ' . $perPage . ' given');
		}
		if($page <= 0) {
			throw new \InvalidArgumentException('Page should be a positive number, ' . $perPage . ' given');
		}
		$from = ($page - 1) * $perPage; // left included
		$to = $from + $perPage; // right excluded

		$counter = 0;
		foreach($items as $k => $item) {
			if($counter < $from || $counter >= $to) {
				unset($items[$k]);
			}
			$counter++;
		}
	}
}
