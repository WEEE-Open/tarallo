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
}
