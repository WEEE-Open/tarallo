<?php

namespace WEEEOpen\Tarallo\Database;


use WEEEOpen\Tarallo\ItemWithCode;

class AuditDAO extends DAO {
	/**
	 * Get audit table entries, most recent first
	 *
	 * @param int $perPage Entries per page
	 * @param int $page Current page, from 1
	 *
	 * @return array
	 */
	public function getRecentAudit(int $perPage, int $page) {
		$statement = $this->getPDO()->prepare(
			'SELECT `Code` AS `code`, `Change` AS `change`, Other AS `other`, UNIX_TIMESTAMP(`Time`) AS `time`, `User` AS `user`
FROM Audit
ORDER BY `time` DESC, `code` DESC, `change`
LIMIT :offs, :cnt'
		);

		try {
			$statement->bindValue(':offs', ($page - 1) * $perPage, \PDO::PARAM_INT);
			$statement->bindValue(':cnt', $perPage, \PDO::PARAM_INT);

			$result = $statement->execute();
			assert($result !== false, 'get audit');

			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}
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

		$statement = $this->getPDO()->prepare(
			'SELECT `Code`, UNIX_TIMESTAMP(`Time`) AS `Time`
FROM Audit
WHERE `Change` = ?
GROUP BY `Code`
ORDER BY `Time` DESC, `Code` DESC
LIMIT ?'
		);

		$result = $statement->execute([$type, $howMany]);
		assert($result !== false, 'get audit by type');

		try {
			foreach($statement as $row) {
				$array[$row['Code']] = $row['Time'];
			}
		} finally {
			$statement->closeCursor();
		}

		return $array;
	}

	/**
	 * Get history entries for an item
	 *
	 * @param ItemWithCode $item
	 * @param int $howMany
	 *
	 * @return array
	 */
	public function getHistory(ItemWithCode $item, int $howMany) {
		$statement = $this->getPDO()->prepare(
			'SELECT `Change` as `change`, Other as other, UNIX_TIMESTAMP(`Time`) AS `time`, `User` as `user`
FROM Audit
WHERE `Code` = ?
ORDER BY `Time` DESC, `Change` DESC
LIMIT ?'
		);

		$result = $statement->execute([$item->getCode(), $howMany]);
		assert($result !== false, 'get history');

		try {
			// TODO: a class rather than returning giant associative arrays, maybe...
			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$statement->closeCursor();
		}
	}
}