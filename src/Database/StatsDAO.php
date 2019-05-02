<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\ItemIncomplete;

final class StatsDAO extends DAO {
	/**
	 * Get an AND for a WHERE clause that filters items by their location.
	 * Bind :loc to the location.
	 *
	 * @param null|ItemIncomplete $location if null returns an empty string
	 * @param string $alias Table alias, if you're doing "SELECT ItemFeatures AS alias", empty string if none
	 *
	 * @return string part of a query
	 */
	private static function filterLocation(?ItemIncomplete $location, string $alias = '') {
		if($location === null) {
			return '';
		}

		if($alias !== '') {
			$alias .= '.';
		}

		return "AND $alias`Code` IN (
SELECT Descendant
FROM Tree
WHERE Ancestor = :loc
)";
	}

	/**
	 * Get an AND for a WHERE clause that filters items by creation date (later than the specified one).
	 * Bind :timestamp to the unix timestamp.
	 *
	 * @param null|\DateTime $creation if null returns an empty string
	 * @param string $alias Table alias, if you're doing "SELECT ItemFeatures AS alias", empty string if none
	 *
	 * @return string part of a query
	 */
	private static function filterCreated(?\DateTime $creation, string $alias = '') {
		if($creation === null) {
			return '';
		}

		if($alias !== '') {
			$alias .= '.';
		}

		return "AND $alias`Code` NOT IN (
SELECT `Code`
FROM Audit
WHERE `Change` = \"C\"
AND `Time` < FROM_UNIXTIME(:timestamp)
)";
	}

	/**
	 * Get an AND for a WHERE clause that ignores deleted & lost items.
	 *
	 * @param string $alias Table alias, if you're doing "SELECT ... FROM ItemFeatures AS alias", empty string if none
	 *
	 * @return string part of a query
	 */
	private static function filterDeletedLost(string $alias = '') {
		if($alias !== '') {
			$alias .= '.';
		}

		return "AND $alias`Code` NOT IN (SELECT `Code` FROM `Item` WHERE DeletedAt IS NOT NULL OR LostAt IS NOT NULL)";
	}

	/**
	 * Get a filter for selecting items with a feature.
	 * Bind paramenters to :ffname1 & :ffval1, :ffname2 & ffval2, etc...
	 *
	 * @param Feature[] $features
	 *
	 * @return string AND `Code` IN (...) AND `Code` IN (...) ...
	 */
	private static function filterFeatures(array $features): string {
		$sqlFilter = '';
		for($i = 0; $i < count($features); $i++) {
			$sqlFilter .= "AND `Code` IN (
			  SELECT `Code`
			  FROM ItemFeature
			  WHERE Feature = :ffname$i AND COALESCE(ValueEnum, `Value`, ValueText, ValueDouble) = :ffval$i
			)";
		}
		return $sqlFilter;
	}

	/**
	 * Get a list of all locations, ordered by number of items inside each one.
	 * Ignores deleted/lost items as they aren't placed anywhere.
	 * No filtering by location because that doesn't make sense.
	 *
	 * @return array
	 */
	public function getLocationsByItems() {
		$array = [];

		$result = $this->getPDO()->query(
		/** @lang MySQL */
			<<<'EOQ'
SELECT `Code` AS Location, COUNT(*) - 1 AS Descendants
FROM ItemFeature, Tree
WHERE ItemFeature.Code = Tree.Ancestor
AND ItemFeature.Feature = 'type'
AND ItemFeature.ValueEnum = 'location'
AND `Code` NOT IN (SELECT `Code` FROM Item WHERE DeletedAt IS NOT NULL)
GROUP BY Tree.Ancestor
ORDER BY COUNT(*) DESC, Location ASC;
EOQ
			,
			\PDO::FETCH_ASSOC
		);

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
	 * Get most/least recently changed cases in a particular location, excluding in-use ones. This takes into account
	 * all audit entries for all contained items.
	 * Deleted/lost items are ignored since they aren't in any location.
	 *
	 * Any attempt to make the function more generic failed miserably or was escessively complex, but consider
	 * that this is a very specific kind of stat to begin with...
	 * @todo parametrize the "in-use" exclusion, maybe? So the "most recently modified" makes more sense
	 * @todo try to parametrize the "type=case" filter
	 *
	 * @param ItemIncomplete $location Where to look, null to search everywhere
	 * @param bool $recent True for more recently modified items first, false for least recently modified
	 * @param int $limit rows to return
	 *
	 * @return int[] code => timestamp
	 */
	public function getModifiedItems(?ItemIncomplete $location, bool $recent = true, int $limit = 100): array {
		$array = [];

		if($location !== null) {
			$locationPart = 'AND `Ancestor` IN (
	SELECT Descendant
	FROM Tree
	WHERE Ancestor = :loc
)';
		} else {
			$locationPart = '';
		}

		$query = "SELECT `Ancestor` AS `Item`, `Time`, UNIX_TIMESTAMP(MAX(`Time`)) AS `Last`
FROM Audit
JOIN Tree ON Tree.Descendant=Audit.Code
WHERE `Ancestor` IN (
	SELECT `Code`
	FROM ItemFeature
	WHERE Feature = 'type' AND `ValueEnum` = 'case'
)
$locationPart
AND `Ancestor` NOT IN (
	SELECT `Code`
	FROM ItemFeature
	WHERE Feature = 'restrictions' AND `ValueEnum` = 'in-use'
)
GROUP BY `Ancestor`
ORDER BY `Last` " . ($recent ? 'DESC' : 'ASC') . '
LIMIT :lim';
		$statement = $this->getPDO()->prepare($query);

		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}
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
	 * - ...and so on.
	 *
	 * If some (enum) values aren't assigned to an item they're not reported, actually,
	 * so it's not really every possible value.
	 *
	 * @param string $feature Feature name
	 * @param Feature|null $filter Feature that must match, useful to select items by type
	 * @param ItemIncomplete $location Consider only this subtree
	 * @param null|\DateTime $creation Creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 * @param int $cutoff Report features only if count is greater than (or equal to) this number,
	 * useful for text features with lots of possible values
	 *
	 * @return int[] value => count, sorted by count descending
	 */
	public function getCountByFeature(
		string $feature,
		?Feature $filter,
		?ItemIncomplete $location = null,
		?\DateTime $creation = null,
		bool $deleted = false,
		int $cutoff = 1
	) {
		Feature::validateFeatureName($feature);

		$array = [];

		$locationFilter = self::filterLocation($location);
		$deletedFilter = $deleted ? '' : self::filterDeletedLost();
		$createdFilter = self::filterCreated($creation);
		if($filter === null) {
			$featureFilter = '';
		} else {
			$featureFilter = 'AND `Code` IN (
  SELECT `Code`
  FROM ItemFeature
  WHERE Feature = :filtername AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = :filtervalue
)';
		}

		$query = "SELECT COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) as Val, COUNT(*) AS Quantity
FROM ItemFeature
WHERE Feature = :feat
$featureFilter
$locationFilter
$deletedFilter
$createdFilter
GROUP BY Val
HAVING Quantity >= :cutoff
ORDER BY Quantity DESC, Val ASC";

		$statement = $this->getPDO()->prepare($query);

		$statement->bindValue(':feat', $feature, \PDO::PARAM_STR);
		$statement->bindValue(':cutoff', $cutoff, \PDO::PARAM_INT);
		if($filter !== null) {
			$statement->bindValue(':filtername', $filter->name, \PDO::PARAM_STR);
			$statement->bindValue(':filtervalue', $filter->value);
		}
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}
		if($creation !== null) {
			$statement->bindValue(':timestamp', $creation->getTimestamp(), \PDO::PARAM_INT);
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
	 * @param null|\DateTime $creation creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return ItemIncomplete[] Items that have that feature (or empty array if none)
	 */
	public function getItemsByFeatures(
		Feature $feature,
		?ItemIncomplete $location = null,
		?int $limit = null, // TODO: $limit === null won't work (see getLostItem to do it correctly)
		?\DateTime $creation = null,
		bool $deleted = false
	): array {
		$pdo = $this->getPDO();
		$locationFilter = self::filterLocation($location);
		$deletedFilter = $deleted ? '' : self::filterDeletedLost();
		$createdFilter = self::filterCreated($creation);

		/** @noinspection SqlResolve */
		$query = "SELECT `Code`
FROM ItemFeature
WHERE Feature = :feat
AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = :val
$locationFilter
$deletedFilter
$createdFilter
LIMIT :lim";
		$statement = $pdo->prepare($query);

		$statement->bindValue(':feat', $feature->name, \PDO::PARAM_STR);
		$statement->bindValue(':val', $feature->value);
		$statement->bindValue(':lim', $limit, \PDO::PARAM_INT);
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}
		if($creation !== null) {
			$statement->bindValue(':timestamp', $creation->getTimestamp(), \PDO::PARAM_INT);
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

	/**
	 * Get items that are marked as lost
	 *
	 * @param Feature[] $features Only consider items with these features, empty array for no filters
	 * @param int|null $limit Maximum number of results, null for no limit
	 * @param bool $deleted Also return deleted items, defaults to false (don't return them)
	 *
	 * @return array
	 */
	public function getLostItems(array $features = [], ?int $limit = null, $deleted = false): array {
		$pdo = $this->getPDO();
		$deletedFilter = $deleted ? '' : 'AND DeletedAt IS NULL';

		$featuresFilter = self::filterFeatures($features);

		$limitFilter = $limit === null ? '' : 'LIMIT :lim';

		/** @noinspection SqlResolve */
		$query = "SELECT `Code`
FROM Item
WHERE LostAt IS NOT NULL
$deletedFilter
$featuresFilter
$limitFilter";
		$statement = $pdo->prepare($query);

		for($i = 0; $i < count($features); $i++) {
			$statement->bindValue(":ffname$i", $features[$i]->name, \PDO::PARAM_STR);
			$statement->bindValue(":ffval$i", $features[$i]->value);
		}
		if($limit !== null) {
			$statement->bindValue(':lim', $limit, \PDO::PARAM_INT);
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

	/**
	 * Get all items that don't have a feature at all.
	 *
	 * @param Feature $filter feature that should be there
	 * (to at least set item type, you'll need it unless you want to receive the entire database, basically...)
	 * @param string $notFeature Feature that should not be present at all
	 * @param null|ItemIncomplete $location
	 * @param int $limit Maximum number of results
	 * @param null|\DateTime $creation creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return ItemIncomplete[] Items that have that feature (or empty array if none)
	 */
	public function getItemByNotFeature(
		Feature $filter,
		string $notFeature,
		?ItemIncomplete $location = null,
		int $limit = 100,
		?\DateTime $creation = null,
		bool $deleted = false
	): array {

		$locationFilter = self::filterLocation($location);
		$deletedFilter = $deleted ? '' : self::filterDeletedLost();
		$createdFilter = self::filterCreated($creation);

		$query = "SELECT Code 
FROM ItemFeature 
WHERE Feature = :type 
AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = :val
$locationFilter
$deletedFilter
$createdFilter
AND Code NOT IN ( 
SELECT `Code` 
FROM ItemFeature 
WHERE Feature = :notF
)
LIMIT :lim";
		$statement = $this->getPDO()->prepare($query);

		$statement->bindValue(':type', $filter->name, \PDO::PARAM_STR);
		$statement->bindValue(':val', $filter->value);
		$statement->bindValue(':notF', $notFeature, \PDO::PARAM_STR);
		$statement->bindValue(':lim', $limit, \PDO::PARAM_INT);
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}
		if($creation !== null) {
			$statement->bindValue(':timestamp', $creation->getTimestamp(), \PDO::PARAM_INT);
		}

		$result = [];

		try {
			$success = $statement->execute();
			assert($success, 'get items by NOT features');
			while($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$result[] = new ItemIncomplete($row['Code']);
			}
		} finally {
			$statement->closeCursor();
		}

		return $result;
	}

	/**
	 * Count all items by feature values, for any number of features, with rollup (partial results)
	 *
	 * @param Feature $filter feature that should be there (use to select item type, possibly)
	 * @param string[] $features which columns you want in the results table. Order is preserved.
	 * @param null|ItemIncomplete $location Only consider items in this location
	 * @param null|\DateTime $creation creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return array[] Array of rows, as returned by the database: for N features there are N columns with feature values, the a count column at the end.
	 */
	public function getRollupCountByFeature(
		Feature $filter,
		array $features,
		?ItemIncomplete $location = null,
		?\DateTime $creation = null,
		bool $deleted = false
	): array {
		/*
		 * This was a nice and readable query that rolls up (with a series of join(t)s, manco a farlo apposta...) the
		 * RAMs. To make it generic it became almost unreadable, but the final result should be somewhat like this...
		 *
		 * SELECT a.ValueEnum AS Type,
		 * b.ValueEnum AS FormFactor,
		 * c.Value AS Frequency,
		 * COUNT(*) AS Quantity
		 * FROM ItemFeature AS a
		 * JOIN ItemFeature AS b ON a.Code=b.Code
		 * JOIN ItemFeature AS c ON b.Code=c.Code
		 * WHERE a.Feature = 'ram-type'
		 *   AND b.feature = 'ram-form-factor'
		 *   AND c.Feature = 'frequency-hertz'
		 *   AND a.Code IN (
		 *     SELECT Code
		 *     FROM ItemFeature
		 *     WHERE Feature = 'type'
		 *     AND ValueEnum = 'ram'
		 * )
		 * GROUP BY Type, FormFactor, Frequency WITH ROLLUP;
		 */
		if(empty($features)) {
			throw new \LogicException('Nothing roll up in');
		}
		// Remove any manually set array keys, since these will go into te query without any sanitizations.
		// This guarantees there are only numbers.
		$features = array_values($features);

		$locationFilter = self::filterLocation($location, 'f0');
		$deletedFilter = $deleted ? '' : self::filterDeletedLost('f0');
		$createdFilter = self::filterCreated($creation, 'f0');

		$select = 'SELECT ';
		$from = 'FROM ItemFeature AS f0 '; // $f0 is guaranteed to exist, since the array is not empty
		$where = 'WHERE f0.`Code` IN (
  SELECT `Code`
  FROM ItemFeature
  WHERE Feature = :nam AND COALESCE(ValueEnum, `Value`, ValueText, ValueDouble) = :val
) ';
		// Will produce e.g. `ram-type` ASC,`ram-form-factor` ASC,`frequency-hertz` ASC
		$group = implode("` ASC,`", $features);
		$group = "`$group` ASC";

		foreach($features as $i => $feature) {
			// Can't do it with coalesce, numeric features end up in wrong order...
			$column = Feature::getColumn(Feature::getType($feature));
			$select .= "f$i.$column AS `$feature`, ";
			if($i > 0) {
				$from .= " JOIN ItemFeature AS f$i ON f0.Code=f$i.Code";
			}
			$where .= " AND f$i.`Feature` = :fname$i";
		}
		$select .= 'COUNT(*) AS Quantity';

		$query = "$select
$from
$where
$locationFilter
$deletedFilter
$createdFilter
GROUP BY $group WITH ROLLUP";
		$statement = $this->getPDO()->prepare($query);

		foreach($features as $i => $feature) {
			$statement->bindValue(":fname$i", $feature);
		}
		$statement->bindValue(':nam', $filter->name, \PDO::PARAM_STR);
		$statement->bindValue(':val', $filter->value);
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}
		if($creation !== null) {
			$statement->bindValue(':timestamp', $creation->getTimestamp(), \PDO::PARAM_INT);
		}

		try {
			$success = $statement->execute();
			assert($success, 'get rollup count');
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
			// Cast integers to integers, doubles to doubles... basically ignore this part and imagine that MySQL
			// returns the correct type even with COALESCE
			$cast = [];
			foreach($features as $feature) {
				if(Feature::getType($feature) === Feature::INTEGER || Feature::getType($feature) === Feature::DOUBLE) {
					$cast[] = $feature;
				}
			}
			if(!empty($cast)) {
				foreach($result as &$row) {
					foreach($cast as $feature) {
						if($row[$feature] !== null) {
							if(Feature::getType($feature) === Feature::INTEGER) {
								$row[$feature] = (int) $row[$feature];
							} else if(Feature::getType($feature) === Feature::DOUBLE) {
								$row[$feature] = (double) $row[$feature];
							}
						}
					}
				}
			}
			return $result;
		} finally {
			$statement->closeCursor();
		}
	}
}
