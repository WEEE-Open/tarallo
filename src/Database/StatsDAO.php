<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\ItemIncomplete;

final class StatsDAO extends DAO {
	/**
	 * Get an AND for a WHERE clause that filters items by their location.
	 * Bind :loc to the location.
	 *
	 * @param null|ItemIncomplete $location , if null returns an empty string
	 * @return string part of a query
	 */
	private static function filterLocation(?ItemIncomplete $location) {
		if($location === null) {
			return '';
		}

		return 'AND `Code` IN (
SELECT Descendant
FROM Tree
WHERE Ancestor = :loc
)';
	}

	/**
	 * Get an AND for a WHERE clause that ignores deleted items.
	 *
	 * @return string part of a query
	 */
	private static function filterDeleted() {
		return 'AND `Code` NOT IN (SELECT `Code` FROM `Item` WHERE DeletedAt IS NOT NULL)';
	}

	/**
	 * Get a list of all locations, ordered by number of items inside each one.
	 * Ignores deleted items as they aren't placed anywhere.
	 * No filtering by location because that doesn't make sense.
	 *
	 * @return array
	 */
	public function getLocationsByItems() {
		$array = [];

		$result = $this->getPDO()->query('SELECT `Code` AS Location, COUNT(*) - 1 AS Descendants
FROM ItemFeature, Tree
WHERE ItemFeature.Code = Tree.Ancestor
AND ItemFeature.Feature = \'type\'
AND ItemFeature.ValueEnum = \'location\'
GROUP BY Tree.Ancestor
ORDER BY COUNT(*) DESC, Location ASC;', \PDO::FETCH_ASSOC);

		assert($result !== false, 'available locations');

		try {
			foreach($result as $row) {
				$array[$row['Location']] = $row['Descendants'];
			}
		} finally {
			$result->closeCursor();
		}

		return $array;
	}

	/**
	 * Count duplicate serial numbers.
	 * Considers deleted items too, because yes.
	 * No filtering by location because that doesn't make sense.
	 *
	 * @return array
	 */
	public function getDuplicateSerialsCount() {
		$array = [];

		$result = $this->getPDO()->query('SELECT ValueText AS SN, COUNT(*) AS Count
FROM ItemFeature
WHERE Feature = \'sn\'
GROUP BY ValueText
HAVING Count > 1
ORDER BY Count DESC, SN ASC', \PDO::FETCH_ASSOC);

		assert($result !== false, 'duplicate serial numbers');

		try {
			foreach($result as $row) {
				$array[$row['SN']] = $row['Count'];
			}
		} finally {
			$result->closeCursor();
		}

		return $array;
	}

	/**
	 * Get most/least recently changed cases in a particular location, excluding in-use ones. This takes into account
	 * all audit entries for all contained items.
	 * Deleted items are ignored since they aren't in any location.
	 *
	 * Any attempt to make the function more generic failed miserably or was escessively complex, but consider
	 * that this is a very specific kind of stat to begin with...
	 * @todo parametrize the "in-use" exclusion, maybe? So the "most recently modified" makes more sense
	 * @todo try to parametrize the "type=case" filter
	 *
	 * @param ItemIncomplete $location Where to look
	 * @param bool $recent True for more recently modified items first, false for least recently modified
	 * @param int $limit rows to return
	 *
	 * @return int[] code => timestamp
	 */
	public function getModifiedItems(ItemIncomplete $location, bool $recent = true, int $limit = 100): array {
		$array = [];

		$query = "SELECT `Ancestor` AS `Item`, `Time`, UNIX_TIMESTAMP(MAX(`Time`)) AS `Last`
FROM Audit
JOIN Tree ON Tree.Descendant=Audit.Code
	WHERE `Ancestor` IN (
	SELECT Descendant
	FROM Tree
	WHERE Ancestor = :loc
)
AND `Ancestor` IN (
	SELECT `Code`
	FROM ItemFeature
	WHERE Feature = 'type' AND `ValueEnum` = 'case'
)
AND `Ancestor` NOT IN (
	SELECT `Code`
	FROM ItemFeature
	WHERE Feature = 'restrictions' AND `ValueEnum` = 'in-use'
)
GROUP BY `Ancestor`
ORDER BY `Last` " . ($recent ? 'DESC' : 'ASC') . '
LIMIT :lim';
		$statement = $this->getPDO()->prepare($query);

		$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		$statement->bindValue(':lim', $limit, \PDO::PARAM_INT);

		try {
			$success = $statement->execute();
			assert($success);

			while($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['Item']] = $row['Last'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	/**
	 * Count how many items have each possible value for a feature
	 *
	 * e.g. with feature name = "color":
	 * - red: 10
	 * - yellow: 6
	 * - grey: 4
	 * and so on.
	 *
	 * If some (enum) values aren't assigned to an item they're not reported, actually,
	 * so it's not really every possible value.
	 *
	 * @param string $feature Feature name
	 * @param Feature $filter
	 * @param ItemIncomplete $location
	 * @param bool $deleted Also count deleted items, defaults to false (don't count them)
	 * @return int[] value => count, sorted by count descending
	 */
	public function getCountByFeature(string $feature, Feature $filter, ?ItemIncomplete $location = null, bool $deleted = false) {
		Feature::validateFeatureName($feature);

		$array = [];

		$locationFilter = self::filterLocation($location);
		$deletedFilter = $deleted ? '' : self::filterDeleted();

        $query = "SELECT COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) as Val, COUNT(*) AS Quantity
FROM ItemFeature
WHERE Feature = :feat
AND `Code` IN (
  SELECT `Code`
  FROM ItemFeature
  WHERE Feature = :nam AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = :val
)
$locationFilter
$deletedFilter
GROUP BY Val
ORDER BY Quantity DESC";

		$statement = $this->getPDO()->prepare($query);

		$statement->bindValue(':feat', $feature, \PDO::PARAM_STR);
		$statement->bindValue(':val', $filter->value);
		$statement->bindValue(':nam', $filter->name, \PDO::PARAM_STR);
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}

		try {
			$success = $statement->execute();
			assert($success, 'count by feature');
			while($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['Val']] = $row['Quantity'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	/**
	 * Get all items that have a certain value (exact match) for a feature.
	 * For anything more complicated use SearchDAO facilities.
	 *
	 * @param Feature $feature Feature and value to search
	 * @param int $limit Maximum number of results
	 * @param null|ItemIncomplete $location
	 * @param bool $deleted Also count deleted items, defaults to false (don't count them)
	 *
	 * @return ItemIncomplete[] Items that have that feature (or empty array if none)
	 */
	public function getItemsByFeatures(Feature $feature, ?ItemIncomplete $location = null, int $limit = 100, bool $deleted = false): array {
		$pdo = $this->getPDO();
		$locationFilter = self::filterLocation($location);
		$deletedFilter = $deleted ? '' : self::filterDeleted();

		/** @noinspection SqlResolve */
		$query = "SELECT `Code`
FROM ItemFeature
WHERE Feature = :feat
AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = :val
$locationFilter
$deletedFilter
LIMIT :lim";
		$statement = $pdo->prepare($query);

		$statement->bindValue(':feat', $feature->name, \PDO::PARAM_STR);
		$statement->bindValue(':val', $feature->value);
		$statement->bindValue(':lim', $limit, \PDO::PARAM_INT);
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}

		$result = [];

		try {
			$success = $statement->execute();
			assert($success, 'get items by features');
			while($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$result[] = new ItemIncomplete($row['Code']);
			}
		} finally {
			$statement->closeCursor();
		}

		return $result;
	}
}