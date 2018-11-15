<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\ItemIncomplete;

final class StatsDAO extends DAO {
	public function getLocationsByItems() {
		$array = [];

		$result = $this->getPDO()->query('SELECT `Code` AS Location, COUNT(*) - 1 AS Descendants
FROM ItemFeature, Tree
WHERE ItemFeature.Code = Tree.Ancestor
AND ItemFeature.Feature = \'type\'
AND ItemFeature.ValueEnum = \'location\'
GROUP BY Tree.Ancestor
ORDER BY COUNT(*) DESC, Location ASC;', \PDO::FETCH_ASSOC);

		assert($result, 'available locations');

		try {
			foreach($result as $row) {
				$array[$row['Location']] = $row['Descendants'];
			}
		} finally {
			$result->closeCursor();
		}

		return $array;
	}

	public function getDuplicateSerialsCount() {
		$array = [];

		$result = $this->getPDO()->query('SELECT ValueText AS SN, COUNT(*) AS Count
FROM ItemFeature
WHERE Feature = \'sn\'
GROUP BY ValueText
HAVING Count > 1
ORDER BY Count DESC, SN ASC', \PDO::FETCH_ASSOC);

		assert($result, 'duplicate serial numbers');

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

	public function getCountByFeature(ItemIncomplete $location, string $feature, Feature $filter) {
		Feature::validateFeatureName($feature);

		$array = [];

		$type = Feature::getColumn($filter->type);

        $query = "SELECT ValueText, COUNT(*) AS Quantity
FROM ItemFeature
WHERE Feature = '" . $feature . "'
AND `Code` IN (
  SELECT Code
  FROM ItemFeature
  WHERE Feature = :nam AND `" . $type . "` = :val
)
AND `Code` IN (
  SELECT Descendant
  FROM Tree
  WHERE Ancestor = :loc
)
GROUP BY ValueText
ORDER BY Quantity DESC";

		$statement = $this->getPDO()->prepare($query);

		$pdoType = $filter->value === Feature::INTEGER ? \PDO::PARAM_INT : \PDO::PARAM_STR;

		$statement->bindValue(':val', $filter->value, $pdoType);
		$statement->bindValue(':nam', $filter->name, \PDO::PARAM_STR);
		$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		try {
			$success = $statement->execute();
			assert($success);
			while($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$array[$row['ValueText']] = $row['Quantity'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}
}