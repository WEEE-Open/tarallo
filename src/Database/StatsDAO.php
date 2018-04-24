<?php

namespace WEEEOpen\Tarallo\Server\Database;

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

		if($result === false) {
			throw new DatabaseException('Available locations query failed for no reason');
		}

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

		if($result === false) {
			throw new DatabaseException('Duplicate serial numbers query failed for no reason');
		}

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
	 * Get recent audit table entries for a type of change, for any item.
	 *
	 * @param string $type C, M, etc...
	 * @param int $howMany How many rows to return (LIMIT)
	 *
	 * @return array
	 */
	public function getRecentAuditByType(string $type, int $howMany) {
		$array = [];

		$statement = $this->getPDO()->prepare('SELECT `Code`, UNIX_TIMESTAMP(`Time`) AS `Time`
FROM Audit
WHERE `Change` = ?
ORDER BY `Time` DESC, `Code` DESC
LIMIT ?');

		$result = $statement->execute([$type, $howMany]);

		if($result === false) {
			throw new DatabaseException('"Get audit" query failed for no reason');
		}

		try {
			foreach($statement as $row) {
				$array[$row['Code']] = $row['Time'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	public function getHistory(ItemIncomplete $item, int $howMany) {
		$statement = $this->getPDO()->prepare('SELECT `Change`, Other, UNIX_TIMESTAMP(`Time`) AS `Time`, `User`
FROM Audit
WHERE `Code` = ?
ORDER BY `Time` DESC, `Change` DESC
LIMIT ?');

		$result = $statement->execute([$item->getCode(), $howMany]);

		if($result === false) {
			throw new DatabaseException('History query failed for no reason');
		}

		try {
			// TODO: a class rather than returning giant associative arrays, maybe...
			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}
	}
}
