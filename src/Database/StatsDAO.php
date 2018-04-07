<?php

namespace WEEEOpen\Tarallo\Server\Database;

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

	public function getRecentlyAdded(int $howMany) {
		$array = [];

		$statement = $this->getPDO()->prepare('SELECT `Code`, UNIX_TIMESTAMP(`Time`) AS `Time`
FROM Audit
WHERE `Change` = \'C\'
ORDER BY `Time` DESC, `Code` DESC
LIMIT ?');

		$result = $statement->execute([$howMany]);

		if($result === false) {
			throw new DatabaseException('Last added items query failed for no reason');
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
}
