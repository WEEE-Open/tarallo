<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\Search;
use WEEEOpen\Tarallo\Server\SearchTriplet;
use WEEEOpen\Tarallo\Server\User;

final class SearchDAO extends DAO {
	private static function getCompare(SearchTriplet $triplet, $id) {
		$feature = $triplet->getAsFeature();
		$operator = $triplet->getCompare();
		switch($feature->type) {
			case Feature::STRING:
				switch($operator) {
					case '=':
					case '<>':
						return "ValueText $operator :param$id";
					case '~':
						return "ValueText LIKE :param$id";
					case '!~':
						return "ValueText NOT LIKE :param$id";
				}
				break;
			case Feature::INTEGER:
			case Feature::DOUBLE:
				$column = $feature->type === Feature::INTEGER ? 'Value' : 'ValueDouble';
				switch($operator) {
					case '>':
					case '<':
					case '>=':
					case '<=':
					case '<>':
					case '=':
						return "$column $operator :param$id";
				}
				break;
			case Feature::ENUM:
				switch($operator) {
					case '=':
					case '<>':
						return "ValueEnum $operator :param$id";
				}
				break;
		}
		throw new \InvalidArgumentException("Cannot apply filter $triplet");
	}

	/**
	 * Begin searching. By obtaining an ID for this search, setting its expiration date, and the like.
	 *
	 * @param User $user
	 *
	 * @return string
	 */
	private function newSearch(User $user) {
		$s = $this->getPDO()->prepare('INSERT INTO SearchResults(`Owner`) VALUES ?');
		$result = $s->execute([$user->getUsername()]);

		if($result) {
			return $this->getPDO()->lastInsertId();
		} else {
			throw new DatabaseException('Cannot start search for unfathomable reasons');
		}
	}

	public function search(Search $search, User $user, $previousSearchId = null) {
		$i = 0;
		$subqueries = [];

		if($previousSearchId === null) {
			$id = self::newSearch($user);
		} else {
			$id = $previousSearchId;
		}

		foreach($search->searchFeatures as $triplet) {
			/** @var $triplet SearchTriplet */
			$name = $triplet->getKey();
			$compare = self::getCompare($triplet, $i);

			$subqueries[] = /** @lang MySQL */
				<<<EOQ
				SELECT `Code`
				FROM ItemFeature -- , ProductFeature
				WHERE Feature = $name
				AND $compare
				-- AND Item.Brand=ProductFeature.Brand
				-- AND Item.Model=ProductFeature.Model
				-- AND Item.Variant=ProductFeature.Variant
EOQ;
			$i++;
		}

		foreach($search->searchLocations as $location) {
			$subqueries[] = /** @lang MySQL */
				<<<EOQ
			SELECT `Descendant`
			FROM Tree
			WHERE Ancestor = :param$i
EOQ;
			$i++;
		}

		foreach($search->searchAncestors as $ancestorTriplet) {
			/** @var $ancestorTriplet SearchTriplet */
			$name = $ancestorTriplet->getKey();
			$compare = self::getCompare($ancestorTriplet, $i);

			$subqueries[] = /** @lang MySQL */
				<<<EOQ
			SELECT `Descendant`
			FROM ItemFeature, Tree
			WHERE ItemFeature.Code=Tree.Ancestor
			AND Feature = $name
			AND $compare
EOQ;
			$i++;
		}

		if($search->searchCode === null) {
			$codeSubquery = '';
		} else {
			$codeSubquery = '`Code` LIKE :cs';
		}

		$everything = '';
		foreach($subqueries as $subquery) {
			$everything .= "AND Item.`Code` IN (\n";
			$everything .= $subquery;
			$everything .= ")\n";
		}

		if($codeSubquery !== '') {
			$everything .= "AND $codeSubquery";
		}

		// Replace first AND with WHERE
		$everything = 'WHERE' . substr($everything, 3);

		$megaquery = /** @lang MySQL */
			<<<EOQ
INSERT INTO SearchResult(Search, Item)
SELECT :searchId, Item.`Code`
FROM Item, ItemFeature
$everything
ORDER BY Item.`Code`;
EOQ;

		$statement = $this->getPDO()->prepare($megaquery);
		$statement->bindValue(":searchId", $id, \PDO::PARAM_INT);

		$i = 0;
		foreach($search->searchFeatures as $triplet) {
			$pdoType = $triplet->getAsFeature()->value === Feature::INTEGER ? \PDO::PARAM_INT : \PDO::PARAM_STR;
			$statement->bindValue(":param$i", $triplet->getValue(), $pdoType);
			$i++;
		}
		foreach($search->searchLocations as $location) {
			$statement->bindValue(":param$i", $location, \PDO::PARAM_STR);
			$i++;
		}
		foreach($search->searchAncestors as $triplet) {
			$pdoType = $triplet->getAsFeature()->value === Feature::INTEGER ? \PDO::PARAM_INT : \PDO::PARAM_STR;
			$statement->bindValue(":param$i", $triplet->getValue(), $pdoType);
			$i++;
		}
		foreach($search->searchFeatures as $triplet) {
			$pdoType = $triplet->getAsFeature()->value === Feature::INTEGER ? \PDO::PARAM_INT : \PDO::PARAM_STR;
			$statement->bindValue(":param$i", $triplet->getValue(), $pdoType);
			$i++;
		}
		if($search->searchCode !== null) {
			$statement->bindValue(":cs", $search->searchCode);
		}
		if(!$statement->execute()) {
			throw new DatabaseException('Cannot execute search for unknown reasons');
		}

		// TODO: sorting

		return $id;
	}

	// TODO: everything
	public function retrieveResults($id) {
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

}
