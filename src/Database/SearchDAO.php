<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
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
				$column = Feature::getColumn($feature->type);
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
	 * @param int $previousSearchId
	 *
	 * @return int
	 */
	public function getResultsCount(int $previousSearchId) {
		$s = $this->getPDO()->prepare('SELECT ResultsCount FROM Search WHERE Code = ?;');
		$result = $s->execute([$previousSearchId]);
		assert($result !== false, 'get results count');

		try {
			if($s->rowCount() === 0) {
				throw new \LogicException("Search id $previousSearchId doesn't exist");
			}
			$row = $s->fetch(\PDO::FETCH_NUM);

			return (int) $row[0];
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * @param int $searchId
	 *
	 * @return string
	 */
	public function getOwnerUsername(int $searchId) {
		$s = $this->getPDO()->prepare('SELECT Owner FROM Search WHERE Code = ?;');
		$result = $s->execute([$searchId]);
		assert($result !== false, 'get search owner username');
		try {
			if($s->rowCount() === 0) {
				throw new \LogicException("Search id $searchId doesn't exist");
			}
			$row = $s->fetch(\PDO::FETCH_NUM);

			return $row[0];
		} finally {
			$s->closeCursor();
		}
	}

	/**
	 * Begin searching. By obtaining an ID for this search, setting its expiration date, and the like.
	 *
	 * @param User $user
	 *
	 * @return int
	 */
	private function newSearch(User $user) {
		$s = $this->getPDO()->prepare('INSERT INTO Search(`Owner`) VALUES (?)');
		$result = $s->execute([$user->getUsername()]);
		assert($result !== false, 'start search');
		return (int) $this->getPDO()->lastInsertId();
	}

	/**
	 * @param Search $search Filters to be applied
	 * @param User $user Search owner (current user), not used if refining a previous search
	 * @param int|null $previousSearchId If supplied, previous results are filtered again
	 *
	 * @return int Search ID, previous or new
	 *
	 * @TODO break up this function in smaller parts, it's huuuuuge
	 */
	public function search(Search $search, User $user, $previousSearchId = null) {
		$i = 0;
		$subqueries = [];

		if($search->isSortOnly()) {
			if($previousSearchId === null) {
				throw new \InvalidArgumentException("Sorting only is not allowed for a new search");
			} else {
				$this->sort($search, $previousSearchId);
				return $previousSearchId;
			}
		}

		if($previousSearchId === null) {
			$id = self::newSearch($user);
		} else {
			$id = $previousSearchId;
		}

		if($search->searchFeatures !== null) {
			foreach($search->searchFeatures as $triplet) {
				/** @var $triplet SearchTriplet */
				$compare = self::getCompare($triplet, $i);

				$subqueries[] = /** @lang MySQL */
					<<<EOQ
				SELECT `Code`
				FROM ItemFeature -- , ProductFeature
				WHERE Feature = :fn$i
				AND $compare
				-- AND Item.Brand=ProductFeature.Brand
				-- AND Item.Model=ProductFeature.Model
				-- AND Item.Variant=ProductFeature.Variant
EOQ;
				$i++;
			}
		}

		if($search->searchLocations !== null) {
			foreach($search->searchLocations as $location) {
				$subqueries[] = /** @lang MySQL */
					<<<EOQ
			SELECT `Descendant`
			FROM Tree
			WHERE Ancestor = :param$i
EOQ;
				$i++;
			}
		}

		if($search->searchAncestors !== null) {
			foreach($search->searchAncestors as $ancestorTriplet) {
				/** @var $ancestorTriplet SearchTriplet */
				$compare = self::getCompare($ancestorTriplet, $i);

				$subqueries[] = /** @lang MySQL */
					<<<EOQ
			SELECT `Descendant`
			FROM ItemFeature, Tree
			WHERE ItemFeature.Code=Tree.Ancestor
			AND Feature = :fn$i
			AND $compare
EOQ;
				$i++;
			}
		}

		if($search->searchCode === null) {
			$codeSubquery = '';
		} else {
			$codeSubquery = 'Item.`Code` LIKE :cs';
		}

		$everything = '';
		foreach($subqueries as $subquery) {
			$everything .= "AND Item.`Code` IN (\n";
			$everything .= $subquery;
			$everything .= "\n)";
		}

		if($codeSubquery !== '') {
			$everything .= "AND $codeSubquery";
		}

		// Replace first AND with WHERE:
		// $everything = 'WHERE' . substr($everything, 3);

		// Or rather:
		$everything = 'WHERE DeletedAt IS NULL ' . $everything;

		if($previousSearchId) {
			$megaquery = /** @lang MySQL */
				<<<EOQ
DELETE FROM SearchResult
WHERE Search = :searchId AND Item NOT IN (SELECT `Code` FROM Item $everything);
EOQ;
		} else {
			$megaquery = /** @lang MySQL */
				<<<EOQ
INSERT INTO SearchResult(Search, Item)
SELECT DISTINCT :searchId, `Code`
FROM Item
$everything;
EOQ;
		}

		$statement = $this->getPDO()->prepare($megaquery);
		$statement->bindValue(':searchId', $id, \PDO::PARAM_INT);

		$i = 0;
		if($search->searchFeatures !== null) {
			foreach($search->searchFeatures as $triplet) {
				$pdoType = $triplet->getAsFeature()->value === Feature::INTEGER ? \PDO::PARAM_INT : \PDO::PARAM_STR;
				$statement->bindValue(":fn$i", $triplet->getKey(), \PDO::PARAM_STR);
				$statement->bindValue(":param$i", $triplet->getValue(), $pdoType);
				$i++;
			}
		}
		if($search->searchLocations !== null) {
			foreach($search->searchLocations as $location) {
				$statement->bindValue(":param$i", $location, \PDO::PARAM_STR);
				$i++;
			}
		}
		if($search->searchAncestors !== null) {
			foreach($search->searchAncestors as $triplet) {
				$pdoType = $triplet->getAsFeature()->value === Feature::INTEGER ? \PDO::PARAM_INT : \PDO::PARAM_STR;
				$statement->bindValue(":fn$i", $triplet->getKey(), \PDO::PARAM_STR);
				$statement->bindValue(":param$i", $triplet->getValue(), $pdoType);
				$i++;
			}
		}
		if($search->searchCode !== null) {
			$statement->bindValue(":cs", $search->searchCode);
		}
		$result = $statement->execute();
		assert($result !== false, 'execute search');

		$this->sort($search, $id);

		return $id;
	}

	public function sortByCode(int $searchId) {
		$query = /** @lang MySQL */
			"SELECT `Item` FROM SearchResult WHERE Search = ? ORDER BY CHAR_LENGTH(`Item`) DESC, `Item` DESC;";
		$sortedStatement = $this->getPDO()->prepare($query);

		try {
			$result = $sortedStatement->execute([$searchId]);
			assert($result !== false, 'sorting with codes');

			$sorted = $sortedStatement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$sortedStatement->closeCursor();
		}

		if(!isset($sorted)) {
			throw new \LogicException('Lost sorted items (by code) along the way somehow');
		}
		if(count($sorted) === 0) {
			return;
		}

		$i = 0;
		foreach($sorted as $result) {
			$this->setItemOrder($searchId, $result['Item'], $i);
			$i++;
		}
	}

	public function sort(Search $search, int $searchId) {
		if($search->sort === null) {
			$this->sortByCode($searchId);

			return;
		}

		if(!is_array($search->sort)) {
			throw new \InvalidArgumentException('"Sorts" must be an array');
		}

		if(empty($search->sort)) {
			$this->sortByCode($searchId);

			return;
		}

		reset($search->sort);
		$featureName = key($search->sort);
		$ascdesc = $search->sort[$featureName] === '+' ? 'ASC' : 'DESC';
		$column = Feature::getColumn(Feature::getType($featureName));

		self::unsort($searchId);

		$miniquery = /** @lang MySQL */ <<<EOQ
SELECT DISTINCT `Item` AS `Code`
FROM SearchResult
LEFT JOIN (
	SELECT `Code`, $column
	FROM ItemFeature
	WHERE Feature = ?
) AS features ON `Item` = features.`Code`
WHERE Search = ?
ORDER BY ISNULL($column), $column $ascdesc, CHAR_LENGTH(`Code`) DESC, `Code` DESC;
EOQ;

		$sortedStatement = $this->getPDO()->prepare($miniquery);
		try {
			$result = $sortedStatement->execute([$featureName, $searchId]);
			assert($result !== false, 'sorting results');

			$sorted = $sortedStatement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$sortedStatement->closeCursor();
		}

		if(!isset($sorted)) {
			throw new \LogicException('Lost sorted items along the way somehow');
		}
		if(count($sorted) === 0) {
			return;
		}

		$i = 0;
		foreach($sorted as $result) {
			$this->setItemOrder($searchId, $result['Code'], $i);
			$i++;
		}
	}

	private $setItemOrderStatement = null;

	/**
	 * Set item position in the results table.
	 *
	 * @param int $searchId Search ID
	 * @param string $code Item code
	 * @param int $position Position in the results
	 */
	private function setItemOrder(int $searchId, string $code, int $position) {
		if($this->setItemOrderStatement === null) {
			$this->setItemOrderStatement = $this->getPDO()
				->prepare('UPDATE SearchResult SET `Order` = :pos WHERE Search = :sea AND Item = :cod');
		}

		try {
			$this->setItemOrderStatement->bindValue(':sea', $searchId, \PDO::PARAM_INT);
			$this->setItemOrderStatement->bindValue(':pos', $position, \PDO::PARAM_INT);
			$this->setItemOrderStatement->bindValue(':cod', $code, \PDO::PARAM_STR);
			$result = $this->setItemOrderStatement->execute();
			assert($result !== false, 'move item in search result to position');
		} finally {
			$this->setItemOrderStatement->closeCursor();
		}
	}

	/**
	 * Remove sorting information from search results
	 *
	 * @param int $searchId
	 */
	private function unsort(int $searchId) {
		$statement = $this->getPDO()->prepare('UPDATE SearchResult SET `Order` = NULL WHERE Search = ?');

		try {
			$result = $statement->execute([$searchId]);
			assert($result !== false, 'unsort search');
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get results from a previous search, already sorted.
	 *
	 * @param int $id Search ID
	 * @param int $page Current page, starting from 1
	 * @param int $perPage Items per page
	 * @param int|null $depth Depth of each Item tree
	 *
	 * @return Item[]
	 */
	public function getResults(int $id, int $page, int $perPage, ?int $depth = null) {
		$this->refresh($id);

		$statement = /** @lang MySQL */
			'SELECT `Item` FROM SearchResult WHERE Search = :id ORDER BY `Order` ASC LIMIT :offs, :cnt';

		$statement = $this->getPDO()->prepare($statement);
		$items = [];
		$itemDAO = $this->database->itemDAO();

		try {
			$statement->bindValue(':id', $id, \PDO::PARAM_INT);
			$statement->bindValue(':offs', ($page - 1) * $perPage, \PDO::PARAM_INT);
			$statement->bindValue(':cnt', $perPage, \PDO::PARAM_INT);
			$statement->execute();
			foreach($statement as $result) {
				$items[] = $itemDAO->getItem(new ItemIncomplete($result['Item']), null, $depth);
			}
		} finally {
			$statement->closeCursor();
		}

		return $items;
	}

	/**
	 * Update search expiration date.
	 * Already done via triggers for INSERT, UPDATE and DELETE operations.
	 *
	 * @param int $id
	 */
	private function refresh(int $id) {
		// ID is an int, no riks of SQL injection...
		$result = $this->getPDO()->exec("CALL RefreshSearch($id)");
		// may be 0 for number of affected rows, or false on failure
		assert($result !== false, 'update expiration date for search');
	}
}
