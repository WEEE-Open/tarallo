<?php

/** @noinspection PhpCastIsUnnecessaryInspection */

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\ProductCode;

final class StatsDAO extends DAO
{
	/**
	 * Get an AND for a WHERE clause that filters items by their location.
	 *
	 * @param null|ItemWithCode $location if null returns an empty string
	 * @param string $alias Table alias, if you're doing "SELECT ItemFeatures AS alias", empty string if none
	 *
	 * @return string part of a query
	 */
	private function filterLocation(?ItemWithCode $location, string $alias = ''): string
	{
		if ($location === null) {
			return '';
		}

		if ($alias !== '') {
			$alias .= '.';
		}

		return "AND $alias`Code` IN (
SELECT Descendant
FROM Tree
WHERE Ancestor = " . $this->getPDO()->quote($location->getCode()) . "
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
	private function filterCreated(?\DateTime $creation, string $alias = ''): string
	{
		if ($creation === null) {
			return '';
		}

		if ($alias !== '') {
			$alias .= '.';
		}

		return "AND $alias`Code` NOT IN (
SELECT `Code`
FROM Audit
WHERE `Change` = \"C\"
AND `Time` < FROM_UNIXTIME(" . $this->getPDO()->quote($creation->getTimestamp()) . ")
)";
	}

	/**
	 * Get an AND for a WHERE clause that ignores deleted & lost items.
	 *
	 * @param string $alias Table alias, if you're doing "SELECT ... FROM ItemFeatures AS alias", empty string if none
	 *
	 * @return string part of a query
	 */
	private static function filterDeletedLost(string $alias = ''): string
	{
		if ($alias !== '') {
			$alias .= '.';
		}

		return "AND $alias`Code` NOT IN (SELECT `Code` FROM `Item` WHERE DeletedAt IS NOT NULL OR LostAt IS NOT NULL)";
	}

	/**
	 * Get a filter for selecting items with a feature.
	 *
	 * @param Feature[] $features
	 *
	 * @return string AND `Code` IN (...) AND `Code` IN (...) ...
	 */
	private function filterFeatures(array $features): string
	{
		$sqlFilter = '';
		foreach ($features as $feature) {
			$sqlFilter .= "AND `Code` IN (
			  SELECT `Code`
			  FROM ProductItemFeatureUnified
			  WHERE Feature = " . $this->getPDO()->quote($feature->name) . " AND COALESCE(ValueEnum, `Value`, ValueText, ValueDouble) = " . $this->getPDO()->quote($feature->value) . "
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
	public function getLocationsTree(): array
	{
		$array = [];

		$notesQuery = $this->getPDO()->query(
			<<<'EOQ'
SELECT Code, ValueText, ValueEnum
FROM ItemFeature
WHERE Code IN (
    SELECT Code
    FROM ProductItemFeatureUnified
    WHERE Feature = 'type'
    AND ValueEnum = 'location'
)
AND (Feature = 'notes' OR Feature = 'color')
EOQ
			,
			\PDO::FETCH_NUM
		);

		assert($notesQuery !== false, 'location notes');

		$notes = [];
		$colors = [];
		try {
			foreach ($notesQuery as $row) {
				if (isset($row[1])) {
					$notes[$row[0]] = $row[1];
				} else {
					$colors[$row[0]] = $row[2];
				}
			}
		} finally {
			$notesQuery->closeCursor();
		}

		$result = $this->getPDO()->query(
		/** @lang MySQL */
			<<<'EOQ'
SELECT Code AS Location, t.Ancestor AS Parent, COUNT(*) - 1 AS Items FROM
(SELECT Ancestor, Descendant, Depth
FROM Tree
WHERE Depth = 1) AS t
RIGHT JOIN ProductItemFeatureUnified ON ProductItemFeatureUnified.Code = t.Descendant
JOIN Tree AS t2 ON ProductItemFeatureUnified.Code = t2.Ancestor
WHERE ProductItemFeatureUnified.Feature = 'type'
AND ProductItemFeatureUnified.ValueEnum = 'location'
AND `Code` NOT IN (SELECT `Code` FROM Item WHERE DeletedAt IS NOT NULL)
GROUP BY Location, t.Ancestor
ORDER BY Items DESC
EOQ
			,
			\PDO::FETCH_ASSOC
		);

		assert($result !== false, 'available locations');

		try {
			$counters = [];
			$locations = [];
			$roots = [];
			foreach ($result as $row) {
				$counters[$row['Location']] = $row['Items'];
				if ($row['Parent'] === null) {
					$roots[] = $row['Location'];
				} else {
					$locations[$row['Parent']][] = $row['Location'];
				}
			}

			foreach ($roots as $root) {
				//$array[] = [0, $root, $counters[$root]];
				$this->parseLocationTree($array, 0, $root, $counters, $locations, $notes, $colors);
			}
		} finally {
			$result->closeCursor();
		}

		return $array;
	}

	private function parseLocationTree(array &$array, int $level, string $name, array $counters, array $locations, array $notes, array $colors)
	{
		$array[] = [$level, $name, $counters[$name], $notes[$name] ?? '', $colors[$name] ?? null];
		if (isset($locations[$name])) {
			foreach ($locations[$name] as $location) {
				$this->parseLocationTree($array, $level + 1, $location, $counters, $locations, $notes, $colors);
			}
		}
	}

	/**
	 * Get most/least recently changed cases in a particular location, excluding in-use ones. This takes into account
	 * all audit entries for all contained items.
	 * Deleted/lost items are ignored since they aren't in any location.
	 *
	 * Any attempt to make the function more generic failed miserably or was escessively complex, but consider
	 * that this is a very specific kind of stat to begin with...
	 *
	 * @param ItemWithCode|null $location Where to look, null to search everywhere
	 * @param bool $recent True for more recently modified items first, false for least recently modified
	 * @param int $limit rows to return
	 *
	 * @return int[] code => timestamp
	 * @todo parametrize the "in-use" exclusion, maybe? So the "most recently modified" makes more sense
	 * @todo try to parametrize the "type=case" filter
	 *
	 */
	public function getModifiedItems(?ItemWithCode $location, bool $recent = true, int $limit = 100): array
	{
		$array = [];

		if ($location !== null) {
			$locationPart = 'AND `Ancestor` IN (
	SELECT Descendant
	FROM Tree
	WHERE Ancestor = ' . $this->getPDO()->quote($location->getCode()) . '
)';
		} else {
			$locationPart = '';
		}

		$query = "SELECT `Ancestor` AS `Item`, `Time`, UNIX_TIMESTAMP(MAX(`Time`)) AS `Last`
FROM Audit
JOIN Tree ON Tree.Descendant=Audit.Code
WHERE `Ancestor` IN (
	SELECT `Code`
	FROM ProductItemFeatureUnified
	WHERE Feature = 'type' AND `ValueEnum` = 'case'
)
$locationPart
AND `Ancestor` NOT IN (
	SELECT `Code`
	FROM ProductItemFeatureUnified
	WHERE Feature = 'restrictions' AND `ValueEnum` = 'in-use'
)
GROUP BY `Ancestor`
ORDER BY `Last` " . ($recent ? 'DESC' : 'ASC') . '
LIMIT ' . (int) $limit;
		$statement = $this->getPDO()->query($query);

		try {
			$success = $statement->execute();
			assert($success);

			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
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
	 * @param ItemWithCode|null $location Consider only this subtree
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
		?ItemWithCode $location = null,
		?\DateTime $creation = null,
		bool $deleted = false,
		int $cutoff = 1
	): array {
		BaseFeature::validateFeatureName($feature);
		$pdo = $this->getPDO();

		$array = [];

		$locationFilter = $this->filterLocation($location);
		$deletedFilter = $deleted ? '' : $this->filterDeletedLost();
		$createdFilter = $this->filterCreated($creation);
		if ($filter === null) {
			$featureFilter = '';
		} else {
			$featureFilter = 'AND `Code` IN (
  SELECT `Code`
  FROM ProductItemFeatureUnified
  WHERE Feature = ' . $pdo->quote($filter->name) . ' AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = ' . $pdo->quote($filter->value) . '
)';
		}

		$query = "SELECT COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) as Val, COUNT(*) AS Quantity
FROM ProductItemFeatureUnified
WHERE Feature = " . $pdo->quote($feature) . "
$featureFilter
$locationFilter
$deletedFilter
$createdFilter
GROUP BY Val
HAVING Quantity >= " . $pdo->quote($cutoff) . "
ORDER BY Quantity DESC, Val";

		$statement = $this->getPDO()->prepare($query);

		try {
			$success = $statement->execute();
			assert($success, 'count by feature');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['Val']] = $row['Quantity'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	/**
	 * Get items for each value of a feature
	 *
	 * e.g. with feature name = "color":
	 * - red: ['R124', 'R252']
	 * - yellow: ['B13', 'B14', 'B42', 'G42']
	 * - grey: ['A46']
	 * - ...and so on.
	 *
	 * If some (enum) values aren't assigned to an item they're not reported.
	 *
	 * @param string $feature Feature name
	 * @param Feature|null $filter Feature that must match, useful to select items by type
	 * @param ItemWithCode|null $location Consider only this subtree
	 * @param null|\DateTime $creation Creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return ItemCode[][] value => array of ItemCode
	 */
	public function getItemsForEachValue(
		string $feature,
		?Feature $filter,
		?ItemWithCode $location = null,
		?\DateTime $creation = null,
		bool $deleted = false
	): array {
		BaseFeature::validateFeatureName($feature);
		$pdo = $this->getPDO();

		$locationFilter = $this->filterLocation($location);
		$deletedFilter = $deleted ? '' : $this->filterDeletedLost();
		$createdFilter = $this->filterCreated($creation);
		if ($filter === null) {
			$featureFilter = '';
		} else {
			$featureFilter = /** @lang MySQL */
				'AND `Code` IN (
  SELECT `Code`
  FROM ProductItemFeatureUnified
  WHERE Feature = ' . $pdo->quote($filter->name) . ' AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = ' . $pdo->quote($filter->value) . '
)';
		}

		$query = "SELECT Code, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) as Val
FROM ProductItemFeatureUnified
WHERE Feature = " . $pdo->quote($feature) . "
$featureFilter
$locationFilter
$deletedFilter
$createdFilter
";

		$statement = $this->getPDO()->prepare($query);

		$array = [];

		try {
			$success = $statement->execute();
			assert($success, 'count by feature');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				if (!isset($array[$row['Val']])) {
					$array[$row['Val']] = [];
				}
				$array[$row['Val']][] = new ItemCode($row['Code']);
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
	 * @param null|ItemWithCode $location
	 * @param int|null $limit Maximum number of results
	 * @param null|\DateTime $creation creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return ItemCode[] Items that have that feature (or empty array if none)
	 */
	public function getItemsByFeatures(
		Feature $feature,
		?ItemWithCode $location = null,
		?int $limit = null,
		?\DateTime $creation = null,
		bool $deleted = false
	): array {
		$pdo = $this->getPDO();
		$locationFilter = self::filterLocation($location);
		$deletedFilter = $deleted ? '' : self::filterDeletedLost();
		$createdFilter = self::filterCreated($creation);
		$limitFilter = $limit === null ? '' : 'LIMIT ' . (int) $limit;

		/** @noinspection SqlResolve */
		$query = "SELECT `Code`
FROM ProductItemFeatureUnified
WHERE Feature = " . $pdo->quote($feature->name) . "
AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = " . $pdo->quote($feature->value) . "
$locationFilter
$deletedFilter
$createdFilter
$limitFilter";
		$statement = $pdo->prepare($query);

		$result = [];

		try {
			$success = $statement->execute();
			assert($success, 'get items by features');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$result[] = new ItemCode($row['Code']);
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
	public function getLostItems(array $features = [], ?int $limit = null, bool $deleted = false): array
	{
		$pdo = $this->getPDO();
		$deletedFilter = $deleted ? '' : 'AND DeletedAt IS NULL';

		$featuresFilter = self::filterFeatures($features);

		$limitFilter = $limit === null ? '' : 'LIMIT ' . (int) $limit;

		/** @noinspection SqlResolve */
		$query = "SELECT `Code`
FROM Item
WHERE LostAt IS NOT NULL
$deletedFilter
$featuresFilter
$limitFilter";
		$statement = $pdo->prepare($query);

		$result = [];

		try {
			$success = $statement->execute();
			assert($success, 'get lost items');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$result[] = new ItemCode($row['Code']);
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
	 * @param null|ItemWithCode $location
	 * @param int $limit Maximum number of results
	 * @param null|\DateTime $creation creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return ItemWithCode[] Items that have that feature (or empty array if none)
	 */
	public function getItemByNotFeature(
		Feature $filter,
		string $notFeature,
		?ItemWithCode $location = null,
		int $limit = 100,
		?\DateTime $creation = null,
		bool $deleted = false
	): array {
		$pdo = $this->getPDO();

		$locationFilter = $this->filterLocation($location);
		$deletedFilter = $deleted ? '' : $this->filterDeletedLost();
		$createdFilter = $this->filterCreated($creation);

		$query = "SELECT Code 
FROM ProductItemFeatureUnified 
WHERE Feature = " . $pdo->quote($filter->name) . "
AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = " . $pdo->quote($filter->value) . "
$locationFilter
$deletedFilter
$createdFilter
AND Code NOT IN ( 
SELECT `Code` 
FROM ProductItemFeatureUnified 
WHERE Feature = " . $pdo->quote($notFeature) . "
)
LIMIT " . (int) $limit;
		$statement = $this->getPDO()->prepare($query);

		$result = [];

		try {
			$success = $statement->execute();
			assert($success, 'get items by NOT features');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$result[] = new ItemCode($row['Code']);
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
	 * @param null|ItemWithCode $location Only consider items in this location
	 * @param null|\DateTime $creation creation date (starts from here)
	 * @param bool $deleted Also count deleted/lost items, defaults to false (don't count them)
	 *
	 * @return array[] Array of rows, as returned by the database: for N features there are N columns with feature values, the a count column at the end.
	 */
	public function getRollupCountByFeature(
		Feature $filter,
		array $features,
		?ItemWithCode $location = null,
		?\DateTime $creation = null,
		bool $deleted = false
	): array {
		if (empty($features)) {
			throw new \LogicException('Nothing roll up in');
		}
		$pdo = $this->getPDO();
		// Remove any manually set array keys, since these will go into te query without any sanitizations.
		// This guarantees there are only numbers.
		$features = array_values($features);

		$locationFilter = $this->filterLocation($location, 'f0');
		$deletedFilter = $deleted ? '' : $this->filterDeletedLost('f0');
		$createdFilter = $this->filterCreated($creation, 'f0');

		$select = 'SELECT ';
		$from = 'FROM ProductItemFeatureUnified AS f0 '; // $f0 is guaranteed to exist, since the array is not empty
		$where = 'WHERE f0.`Code` IN (
  SELECT `Code`
  FROM ProductItemFeatureUnified
  WHERE Feature = ' . $pdo->quote($filter->name) . ' AND COALESCE(ValueEnum, `Value`, ValueText, ValueDouble) = ' . $pdo->quote($filter->value) . '
) ';

		$group = '';
		foreach ($features as $i => $feature) {
			// Can't do it with coalesce, numeric features end up in wrong order...
			$column = FeatureDAO::getColumn(BaseFeature::getType($feature));

			if (!isset(BaseFeature::FEATURES[$feature])) {
				throw new \InvalidArgumentException("$feature is not a feature");
			}
			$fcolname = $feature;
			$select .= "f$i.$column AS `$fcolname`, ";
			if ($i > 0) {
				$from .= " JOIN ProductItemFeatureUnified AS f$i ON f0.Code=f$i.Code";
			}
			$where .= " AND f$i.`Feature` = " . $pdo->quote($feature);
			$group .= "`$fcolname` ASC, ";
		}
		$group = rtrim($group, ', ');
		$select .= 'COUNT(*) AS Quantity';

		$query = "$select
$from
$where
$locationFilter
$deletedFilter
$createdFilter
GROUP BY $group WITH ROLLUP";
		//echo("<pre>$query</pre>");
		$statement = $this->getPDO()->prepare($query);

		try {
			$success = $statement->execute();
			assert($success, 'get rollup count');
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
			// Cast integers to integers, doubles to doubles... basically ignore this part and imagine that MySQL
			// returns the correct type even with COALESCE
			$cast = [];
			foreach ($features as $feature) {
				if (
					BaseFeature::getType($feature) === BaseFeature::INTEGER || BaseFeature::getType(
						$feature
					) === BaseFeature::DOUBLE
				) {
					$cast[] = $feature;
				}
			}
			if (!empty($cast)) {
				foreach ($result as &$row) {
					foreach ($cast as $feature) {
						if ($row[$feature] !== null) {
							if (BaseFeature::getType($feature) === BaseFeature::INTEGER) {
								$row[$feature] = (int) $row[$feature];
							} elseif (BaseFeature::getType($feature) === BaseFeature::DOUBLE) {
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

	/**
	 * Get all products in the database and a count of how many items are there for each one
	 *
	 * @param string|null $brand Get only products with this brand
	 * @param string|null $model Get only products with this model (different variants)
	 *
	 * @return array Associative array with rows of [product, manufacturer|null, family|null, internal|null, items count]
	 */
	public function getAllProducts(?string $brand, ?string $model): array
	{
		$where = [];
		if ($brand !== null) {
			$where[] = 'Product.Brand = :b';
		}
		if ($model !== null) {
			$where[] = 'Product.Model = :m';
		}
		if (count($where) > 0) {
			$where = 'WHERE ' . implode(' AND ', $where);
		} else {
			$where = '';
		}

		$statement = $this->getPDO()->prepare(
			<<<EOQ
			SELECT Product.Brand, Product.Model, Product.Variant, pfManufacturer.ValueText AS Manufacturer, pfFamily.ValueText AS Family, pfInternal.ValueText AS Internal, COUNT(Code) AS Items
			FROM Item
			NATURAL RIGHT JOIN Product
			LEFT OUTER JOIN ProductFeature AS pfManufacturer ON Product.Brand=pfManufacturer.Brand AND Product.Model=pfManufacturer.Model AND Product.Variant=pfManufacturer.Variant AND pfManufacturer.Feature = 'brand-manufacturer'
			LEFT OUTER JOIN ProductFeature AS pfFamily ON Product.Brand=pfFamily.Brand AND Product.Model=pfFamily.Model AND Product.Variant=pfFamily.Variant AND pfFamily.Feature = 'family'
			LEFT OUTER JOIN ProductFeature AS pfInternal ON Product.Brand=pfInternal.Brand AND Product.Model=pfInternal.Model AND Product.Variant=pfInternal.Variant AND pfInternal.Feature = 'internal-name'
			$where
			GROUP BY Product.Brand, Product.Model, Product.Variant
			ORDER BY Product.Brand, Product.Model, Product.Variant, Items DESC
EOQ
		);
		try {
			if ($brand !== null) {
				/** @noinspection PhpRedundantOptionalArgumentInspection */
				$statement->bindValue(':b', $brand, \PDO::PARAM_STR);
			}
			if ($model !== null) {
				/** @noinspection PhpRedundantOptionalArgumentInspection */
				$statement->bindValue(':m', $model, \PDO::PARAM_STR);
			}
			$result = $statement->execute();
			assert($result === true, 'get products and count');
			$result = [];
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$product = new ProductCode($row['Brand'], $row['Model'], $row['Variant']);
				$result[] = [$product, $row['Manufacturer'], $row['Family'], $row['Internal'], $row['Items']];
			}

			return $result;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get all brands in the database and a count of how many products (not items) are there for each one
	 *
	 * @return array Associative array with rows of [brand, products count]
	 */
	public function getAllBrands(): array
	{
		$statement = $this->getPDO()->prepare(
			<<<EOQ
			SELECT Brand, COUNT(*) AS Products
			FROM Product
			GROUP BY Brand
			ORDER BY Brand
EOQ
		);
		try {
			$result = $statement->execute();
			assert($result === true, 'get brands and count');
			return $statement->fetchAll(\PDO::FETCH_NUM);
		} finally {
			$statement->closeCursor();
		}
	}

	public function getAllItemsOfProduct(ProductCode $product): array
	{
		$statement = $this->getPDO()->prepare(
			<<<EOQ
		SELECT Code
		FROM Item
		WHERE Brand = ? AND Model = ? AND Variant = ?
EOQ
		);
		try {
			$result = $statement->execute([$product->getBrand(), $product->getModel(), $product->getVariant()]);
			assert($result === true, 'get items from products and count');
			$result = [];
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$result[] = $this->database->itemDAO()->getItem(new ItemCode($row['Code']));
			}

			return $result;
		} finally {
			$statement->closeCursor();
		}
	}

	public function getProductsCountByBrand(): array
	{
		$statement = $this->getPDO()->prepare(
			<<<EOQ
				SELECT Brand, COUNT(DISTINCT Model) AS Models, COUNT(*) AS Variants, COUNT(*)/COUNT(DISTINCT Model) AS VPP
				FROM Product
				GROUP BY Brand
				ORDER BY Brand
EOQ
		);
		try {
			$result = $statement->execute();
			assert($result === true, 'get products count by brand');

			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}
	}

	public function getItemsWithIncompleteProducts(): array
	{
		$statement = $this->getPDO()->prepare(
			<<<EOQ
				SELECT Item.Code, Item.Brand, Item.Model, Item.Variant, COUNT(DISTINCT Product.Variant) AS NumVariants
				FROM Item
				LEFT JOIN Product ON Item.Brand = Product.Brand AND Item.Model = Product.Model
				LEFT JOIN Product AS ProductAgain ON Item.Brand = ProductAgain.Brand AND Item.Model = ProductAgain.Model AND Item.Variant = ProductAgain.Variant
				WHERE Item.Brand IS NOT NULL AND Item.Model IS NOT NULL AND ProductAgain.Brand IS NULL AND Item.DeletedAt IS NULL
				GROUP BY Item.Code, Item.Brand, Item.Model, Item.Variant
				ORDER BY Brand, Model, Variant, Code
EOQ
		);
		try {
			$result = $statement->execute();
			assert($result === true, 'get incomplete products');

			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}
	}

	public function getSplittableItems(): array
	{
		$statement = $this->getPDO()->prepare(
			<<<EOQ
				SELECT DISTINCT Item.Code, Item.Brand, Item.Model, Item.Variant, COUNT(Feature) AS Features
				FROM ItemFeature
				NATURAL JOIN Item
				LEFT JOIN Product AS ProductAgain ON Item.Brand = ProductAgain.Brand AND Item.Model = ProductAgain.Model AND Item.Variant = ProductAgain.Variant
				WHERE Item.Brand IS NOT NULL AND Item.Model IS NOT NULL AND ProductAgain.Brand IS NULL AND Item.DeletedAt IS NULL
				AND Feature NOT IN ('brand', 'model', 'variant', 'restrictions', 'working', 'cib-qr', 'cib', 'cib-old', 'other-code', 'os-license-version', 'os-license-code', 'mac', 'sn', 'wwn', 'arrival-batch', 'owner', 'data-erased', 'surface-scan', 'smart-data', 'software', 'notes', 'todo', 'check')
				GROUP BY Item.Code, Item.Brand, Item.Model, Item.Variant
				ORDER BY Brand, Model, Variant, Code
EOQ
		);
		try {
			$result = $statement->execute();
			assert($result === true, 'get splittable items');

			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}
	}


	public function getStatsByType(
		bool $returnCount,
		array $dict,
		string $filterName,
		?string $filterValue = '',
		?ItemCode $location = null,
		?array $additionalFilter = [],
		bool $deleted = false
	): array {
		//Array with featureName=>featureValue if filtered
		//featureName=>null if not filtered
		$pdo = $this->getPDO();


		if ($returnCount) {
			$select = "COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) as Val, COUNT(*) AS Quantity";
		} else {
			$select = "`Code`";
		}


		$filterCondition = '';

		BaseFeature::validateFeatureName($filterName);
		$filterCondition .= "Feature = " . $pdo->quote($filterName);
		if ($filterValue !== '') {
			$filterCondition .= " AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = " . $pdo->quote($filterValue);
		}

		foreach ($additionalFilter as $name => $value) {
			if ($value === null) {
				$filterCondition .= "AND `Code` IN (
				  SELECT `Code`
				  FROM ProductItemFeatureUnified
				  WHERE Feature = " . $pdo->quote($name) . ")";
			} else {
				$filterCondition .= "AND `Code` IN (
				  SELECT `Code`
				  FROM ProductItemFeatureUnified
				  WHERE Feature = " . $pdo->quote($name) .
					" AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = " . $pdo->quote($value) . ")";
			}
		}

		$notInCondition = '';
		$inCondition = '';

		foreach ($dict as $featureName => $value) {
			BaseFeature::validateFeatureName($featureName);
			if ($value === null) {
				$notInCondition .= "OR Feature = " . $pdo->quote($featureName);
			} else {
				$inCondition .= "OR (Feature = " . $pdo->quote($featureName) .
					" AND COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) = " . $pdo->quote($value) . ")";
			}
		}

		$condition = '';
		if (!empty($notInCondition)) {
			//Remove the first OR
			$notInCondition = substr($notInCondition, 3);
			$condition .= " AND Code NOT IN (
			SELECT `Code` 
			FROM ProductItemFeatureUnified 
			WHERE " . $notInCondition .
				")";
		}
		if (!empty($inCondition)) {
			//Remove the first OR
			$inCondition = substr($inCondition, 3);
			$condition .= ' AND `Code` IN (
				  SELECT `Code`
				  FROM ProductItemFeatureUnified
				  WHERE ' . $inCondition . ')';
		}

		$condition .= ' ' . $this->filterLocation($location);
		$deletedFilter = $deleted ? '' : $this->filterDeletedLost();

		$query = "SELECT " . $select . "
		FROM ProductItemFeatureUnified
		WHERE " . $filterCondition . $condition . $deletedFilter;

		if ($returnCount) {
			$query .= " GROUP BY Val
			ORDER BY Quantity DESC, Val ASC";
		}

		$statement = $this->getPDO()->prepare($query);
		$array = [];

		try {
			$success = $statement->execute();
			assert($success, 'stats by feature');
			if ($returnCount) {
				while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
					$array[$row['Val']] = $row['Quantity'];
				}
			} else {
				while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
					$array[] = $row['Code'];
				}
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	public function getUsersStats(string $change = '', ?int $limit = 5): array
	{
		if (!ctype_alpha($change) && $change !== '') {
			throw new \DomainException("Wrong input string: $change is not alphabetic");
		}
		$change = strtoupper($change);
		$pdo = $this->getPDO();
		$where = $change ? " WHERE `Change` = " . $pdo->quote($change) : '';
		/** @noinspection SqlResolve */
		$query = "SELECT User, COUNT(*) as Count
		FROM Audit" .
			$where
			. " GROUP BY User 
		ORDER BY Count DESC
		LIMIT " . $limit;

		$statement = $this->getPDO()->prepare($query);
		$array = [];

		try {
			$success = $statement->execute();
			assert($success, 'Users stats');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['User']] = $row['Count'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	public function getLastAudit(?bool $productAudit = false, ?int $limit = 10): array
	{

		$select = $productAudit ? "Brand, Model, Variant, `Change`, User, Time " : "Code, `Change`, Other, User, Time";

		/** @noinspection SqlResolve */
		$query = "SELECT $select
FROM " . ($productAudit ? 'AuditProduct' : 'Audit') . "
ORDER BY Time DESC
LIMIT " . $limit;

		$statement = $this->getPDO()->prepare($query);

		try {
			$success = $statement->execute();
			assert($success, 'Last audit');
			$array = $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	/**
	 * get the total and average capacity of hdds or rams
	 *
	 * @param array $hddsOrRams must have property 'quantity' for each item
	 * @param string $propertyToSum must be 'capacity-decibyte' or 'capacity-byte'
	 *
	 * @return array
	 */
	public function getTotalAndAverageCapacity(array $hddsOrRams, string $propertyToSum): array
	{
		$sumOfCapacity = 0;
		$numberOfItems = 0;
		foreach ($hddsOrRams as $item) {
			// se non possiede , l'oggetto, la proprietà quantity oppure la proprietà 'capacity-decibyte' o 'capacity-byte' cade in errore
			if (!array_key_exists('Quantity', $item) or !array_key_exists($propertyToSum, $item)) {
				throw new \InvalidArgumentException("In Array passed, a item doesn't have the property 'Capacity' or the property inserted is not equal to 'capacity-byte' or to 'capacity-decibyte'");
			}
			if (!$item[$propertyToSum]) {
				continue;
			}
			$sumOfCapacity += ($item[$propertyToSum] * $item['Quantity']);
			$numberOfItems += $item['Quantity'];
		}
		return [
			'totalCapacity' => $sumOfCapacity,
			'averageCapacity' => ($sumOfCapacity / $numberOfItems)
		];
	}

	/**
	 * counts items grouped by type that have serial number
	 * @return array
	 */
	public function countItemsByTypeThatHaveSerialNumber(): array
	{
		$pdo = $this->getPDO();
		$array = [];
		$queryToSelectItemWithSn = " SELECT ValueEnum as Type, Count(*) as Quantità FROM ProductItemFeatureUnified 
        RIGHT JOIN Item ON Item.Code = ProductItemFeatureUnified.Code
        WHERE ProductItemFeatureUnified.Feature = 'type' AND Item.Code IN (
            SELECT ProductItemFeatureUnified.Code
            FROM ProductItemFeatureUnified
            WHERE ProductItemFeatureUnified.Feature = 'sn'
        )
        GROUP BY Type
        ";
		$queryToSelectItemWithoutSn = "SELECT ValueEnum as Type, Count(*) as Quantità FROM ProductItemFeatureUnified 
        RIGHT JOIN Item ON Item.Code = ProductItemFeatureUnified.Code
        WHERE ProductItemFeatureUnified.Feature = 'type' AND Item.Code NOT IN (
            SELECT ProductItemFeatureUnified.Code
            FROM ProductItemFeatureUnified
            WHERE ProductItemFeatureUnified.Feature = 'sn'
        )
        GROUP BY Type
        ";


		$statementToGetItemWithSn = $pdo->prepare($queryToSelectItemWithSn);
		$statementToGetItemWithoutSn = $pdo->prepare($queryToSelectItemWithoutSn);
		try {
			$successItemsWithSn = $statementToGetItemWithSn->execute();
			$successItemsWithoutSn = $statementToGetItemWithoutSn->execute();
			assert($successItemsWithSn, 'items with sn');
			assert($successItemsWithoutSn, 'items without sn');

			while ($row = $statementToGetItemWithSn->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['Type']]['withSn'] = $row['Quantità'];
			}
			while ($row = $statementToGetItemWithoutSn->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['Type']]['withoutSn'] = $row['Quantità'];
			}
		} finally {
			$statementToGetItemWithSn->closeCursor();
			$statementToGetItemWithoutSn->closeCursor();
		}

		return $array;
	}

	/**
	 * returns top $limit most audited items per audit type
	 * @param int $limit Number of items to return
	 *
	 * @return array
	 */
	public function getItemsMostAuditedPerType(int $limit)
	{
		$result = [];
		$pdo = $this->getPDO();

		$stmt = $pdo->prepare("SELECT `Code`, `Change`, `Count`
		FROM (
			SELECT `Code`, `Change`, COUNT(*) AS `Count`, ROW_NUMBER() OVER (PARTITION BY `Change` ORDER BY `Count` DESC) AS n
			FROM Audit
			GROUP BY `Code`, `Change`
		) AS t
		WHERE n <= ? ORDER BY n");

		try {
			$stmt->execute([$limit]);

			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$result[$row['Change']][] = $row;
			}
		} finally {
			$stmt->closeCursor();
		}

		return $result;
	}
}
